<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicationResource\Pages;
use App\Filament\Resources\ApplicationResource\RelationManagers\StatusHistoriesRelationManager;
use App\Models\Application;
use App\Models\ExamSession;
use App\Models\Student;
use App\Services\ApplicationWorkflowService;
use App\Services\PaymentService;
use App\Support\PerimeterScope;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|UnitEnum|null $navigationGroup = 'Examens';

    protected static ?string $navigationLabel = 'Candidatures';

    public static function getEloquentQuery(): Builder
    {
        return PerimeterScope::forApplication(parent::getEloquentQuery());
    }

    /**
     * `reference` n'apparaît jamais en saisie : elle est générée par
     * ApplicationObserver + ApplicationReferenceService à la création.
     */
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Placeholder::make('reference')
                ->label('Référence')
                ->content(fn (?Application $record) => $record?->reference ?? 'Générée automatiquement à la création')
                ->visible(fn (?Application $record) => $record !== null),

            Select::make('student_id')->label('Candidat')->live()
                ->options(fn () => Student::query()->orderBy('last_name')
                    ->get()->mapWithKeys(fn (Student $s) => [$s->id => "{$s->last_name} {$s->first_name}"]))
                ->searchable()->required(),

            Select::make('establishment_id')->label('Établissement')
                ->options(fn (Get $get) => $get('student_id')
                    ? [Student::find($get('student_id'))?->establishment?->id => Student::find($get('student_id'))?->establishment?->name]
                    : [])
                ->required(),

            Select::make('exam_session_id')->label("Session d'examen")
                ->options(fn () => ExamSession::query()->where('status', 'ouverte')->pluck('label', 'id'))
                ->searchable()->required(),

            Select::make('status')->label('Statut')
                ->options(Application::LABELS)->required()->default('brouillon')
                ->helperText('Les changements de statut normaux se font via les actions du workflow (Soumettre, Approuver, Renvoyer en correction, Rejeter...). Ce champ ne sert que pour une correction manuelle exceptionnelle.')
                ->disabled(fn () => auth()->user()?->scopeLevel() !== 'national'),

            Textarea::make('rejection_reason')->label('Motif de rejet / correction')
                ->visible(fn (Get $get) => in_array($get('status'), [
                    Application::STATUS_REJETEE,
                    Application::STATUS_A_CORRIGER_DEPARTEMENT,
                    Application::STATUS_A_CORRIGER_REGION,
                    Application::STATUS_A_CORRIGER_NATIONAL,
                ]))
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Référence')->searchable()->copyable(),
                TextColumn::make('student.last_name')->label('Candidat')
                    ->formatStateUsing(fn (Application $record) => "{$record->student->last_name} {$record->student->first_name}")
                    ->searchable(['last_name', 'first_name']),
                TextColumn::make('examSession.label')->label('Session'),
                TextColumn::make('establishment.name')->label('Établissement')->sortable(),
                TextColumn::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (Application $record) => $record->statusLabel())
                    ->color(fn (string $state) => match ($state) {
                        Application::STATUS_INSCRIT => 'success',
                        Application::STATUS_SOUMISE,
                        Application::STATUS_VALIDEE_DEPARTEMENT,
                        Application::STATUS_VALIDEE_REGION => 'info',
                        Application::STATUS_A_CORRIGER_DEPARTEMENT,
                        Application::STATUS_A_CORRIGER_REGION,
                        Application::STATUS_A_CORRIGER_NATIONAL => 'warning',
                        Application::STATUS_REJETEE, Application::STATUS_ANNULEE => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('submitted_at')->label('Soumise le')->dateTime('d/m/Y H:i'),
            ])
            ->filters([
                SelectFilter::make('exam_session_id')->label('Session')
                    ->options(fn () => ExamSession::query()->pluck('label', 'id')),
                SelectFilter::make('status')->label('Statut')->options(Application::LABELS),
            ])
            ->recordActions(self::workflowActions())
            ->defaultSort('submitted_at', 'desc');
    }

    /**
     * Actions du workflow multi-niveaux, affichées en ligne de tableau et
     * réutilisées sur la page de fiche. Chaque action : (1) se cache
     * d'elle-même si la Policy refuse, (2) délègue tout au
     * ApplicationWorkflowService qui est l'unique autorité côté serveur,
     * (3) traduit une InvalidApplicationTransitionException en
     * notification lisible plutôt qu'une erreur 500.
     *
     * @return array<Action>
     */
    public static function workflowActions(): array
    {
        return [
            Action::make('payFees')
                ->label('Payer les frais')
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->visible(function (Application $record) {
                    $user = auth()->user();

                    if ($user === null || ! $record->requiresPayment() || $record->hasSuccessfulPayment()) {
                        return false;
                    }

                    if (! $user->hasPermissionTo('payments.initiate')) {
                        return false;
                    }

                    return $user->scopeLevel() === 'national'
                        || ($user->scopeLevel() === 'establishment' && $user->establishment_id === $record->establishment_id);
                })
                ->action(function (Application $record) {
                    try {
                        $result = app(PaymentService::class)->initiate($record, auth()->user());

                        Notification::make()
                            ->title('Paiement initié')
                            ->body('Lien de paiement (démonstration) : '.$result['redirect_url'])
                            ->success()
                            ->persistent()
                            ->send();
                    } catch (Exception $exception) {
                        Notification::make()
                            ->title('Action impossible')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('submit')
                ->label('Soumettre')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn (Application $record) => auth()->user()?->can('submit', $record) ?? false)
                ->requiresConfirmation()
                ->modalHeading('Soumettre cette candidature au contrôle départemental ?')
                ->action(fn (Application $record) => self::runWorkflowAction(
                    $record,
                    fn (ApplicationWorkflowService $service) => $service->submit($record, auth()->user())
                )),

            Action::make('approve')
                ->label('Approuver')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (Application $record) => auth()->user()?->can('approve', $record) ?? false)
                ->requiresConfirmation()
                ->modalHeading(fn (Application $record) => 'Approuver le dossier « '.$record->reference.' » ?')
                ->form([
                    Textarea::make('comment')->label('Commentaire (optionnel)'),
                ])
                ->action(fn (Application $record, array $data) => self::runWorkflowAction(
                    $record,
                    fn (ApplicationWorkflowService $service) => $service->approve($record, auth()->user(), $data['comment'] ?? null)
                )),

            Action::make('requestCorrection')
                ->label('Renvoyer en correction')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn (Application $record) => auth()->user()?->can('requestCorrection', $record) ?? false)
                ->form([
                    Textarea::make('comment')->label('Motif de la correction demandée')->required(),
                ])
                ->action(fn (Application $record, array $data) => self::runWorkflowAction(
                    $record,
                    fn (ApplicationWorkflowService $service) => $service->requestCorrection($record, auth()->user(), $data['comment'])
                )),

            Action::make('resubmit')
                ->label('Resoumettre')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->visible(fn (Application $record) => auth()->user()?->can('resubmit', $record) ?? false)
                ->requiresConfirmation()
                ->modalHeading('Confirmer que les corrections ont été apportées et resoumettre le dossier ?')
                ->action(fn (Application $record) => self::runWorkflowAction(
                    $record,
                    fn (ApplicationWorkflowService $service) => $service->resubmit($record, auth()->user())
                )),

            Action::make('reject')
                ->label('Rejeter')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (Application $record) => auth()->user()?->can('reject', $record) ?? false)
                ->form([
                    Textarea::make('reason')->label('Motif du rejet')->required(),
                ])
                ->action(fn (Application $record, array $data) => self::runWorkflowAction(
                    $record,
                    fn (ApplicationWorkflowService $service) => $service->reject($record, auth()->user(), $data['reason'])
                )),

            Action::make('cancel')
                ->label('Annuler')
                ->icon('heroicon-o-trash')
                ->color('gray')
                ->visible(fn (Application $record) => auth()->user()?->can('cancel', $record) ?? false)
                ->requiresConfirmation()
                ->modalHeading('Annuler définitivement cette candidature ?')
                ->action(fn (Application $record) => self::runWorkflowAction(
                    $record,
                    fn (ApplicationWorkflowService $service) => $service->cancel($record, auth()->user())
                )),
        ];
    }

    private static function runWorkflowAction(Application $record, \Closure $callback): void
    {
        try {
            $callback(app(ApplicationWorkflowService::class));

            Notification::make()
                ->title('Statut mis à jour : '.$record->fresh()->statusLabel())
                ->success()
                ->send();
        } catch (Exception $exception) {
            Notification::make()
                ->title('Action impossible')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function getRelations(): array
    {
        return [
            StatusHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'create' => Pages\CreateApplication::route('/create'),
            'edit' => Pages\EditApplication::route('/{record}/edit'),
        ];
    }
}
