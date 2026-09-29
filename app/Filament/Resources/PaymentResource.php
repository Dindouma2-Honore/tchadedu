<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers\TransactionsRelationManager;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Support\PerimeterScope;
use BackedEnum;
use Exception;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * Lecture et réconciliation des paiements (section 18). Aucun champ
 * `status` éditable dans le formulaire : un statut de paiement ne change
 * QUE via PaymentService (callback vérifié ou confirmation manuelle
 * tracée) — jamais par une simple édition de formulaire, y compris pour un
 * compte national. Voir workflowActions() pour les actions autorisées.
 */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Examens';

    protected static ?string $navigationLabel = 'Paiements';

    public static function getEloquentQuery(): Builder
    {
        return PerimeterScope::forPayment(parent::getEloquentQuery());
    }

    public static function canCreate(): bool
    {
        // Un paiement ne se crée jamais depuis ce Resource : il naît de
        // PaymentService::initiate(), déclenché depuis la fiche candidature.
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Placeholder::make('reference')->label('Référence')
                ->content(fn (?Payment $record) => $record?->reference ?? '—'),
            Placeholder::make('application')->label('Dossier')
                ->content(fn (?Payment $record) => $record?->application?->reference ?? '—'),
            Placeholder::make('amount')->label('Montant')
                ->content(fn (?Payment $record) => $record ? number_format((float) $record->amount, 0, ',', ' ').' '.$record->currency : '—'),
            Placeholder::make('status')->label('Statut')
                ->content(fn (?Payment $record) => $record?->statusLabel() ?? '—'),
            Placeholder::make('provider')->label('Prestataire')
                ->content(fn (?Payment $record) => $record?->provider ?? '—'),
            Placeholder::make('paid_at')->label('Payé le')
                ->content(fn (?Payment $record) => $record?->paid_at?->format('d/m/Y H:i') ?? '—'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Référence')->searchable()->copyable(),
                TextColumn::make('application.reference')->label('Dossier')->searchable(),
                TextColumn::make('application.establishment.name')->label('Établissement'),
                TextColumn::make('amount')->label('Montant')
                    ->formatStateUsing(fn (Payment $record) => number_format((float) $record->amount, 0, ',', ' ').' '.$record->currency),
                TextColumn::make('status')->label('Statut')->badge()
                    ->formatStateUsing(fn (Payment $record) => $record->statusLabel())
                    ->color(fn (string $state) => match ($state) {
                        Payment::STATUS_SUCCESS => 'success',
                        Payment::STATUS_INITIATED, Payment::STATUS_PENDING => 'info',
                        Payment::STATUS_FAILED, Payment::STATUS_EXPIRED => 'danger',
                        Payment::STATUS_REFUNDED => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('provider')->label('Prestataire'),
                TextColumn::make('paid_at')->label('Payé le')->dateTime('d/m/Y H:i')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(Payment::LABELS),
            ])
            ->recordActions(self::workflowActions())
            ->defaultSort('created_at', 'desc');
    }

    /** @return array<Action> */
    public static function workflowActions(): array
    {
        return [
            Action::make('downloadReceipt')
                ->label('Télécharger le reçu')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->visible(fn (Payment $record) => $record->receipt !== null)
                ->url(fn (Payment $record) => route('receipts.download', $record->receipt))
                ->openUrlInNewTab(),

            Action::make('manualConfirm')
                ->label('Confirmer manuellement')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (Payment $record) => auth()->user()?->can('manualConfirm', $record) ?? false)
                ->form([
                    Textarea::make('note')->label('Justificatif / note (ex : référence du dépôt bancaire)')->required(),
                ])
                ->action(fn (Payment $record, array $data) => self::runAction(
                    $record,
                    fn (PaymentService $service) => $service->confirmManually($record, auth()->user(), $data['note'])
                )),

            Action::make('refund')
                ->label('Rembourser')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('warning')
                ->visible(fn (Payment $record) => auth()->user()?->can('refund', $record) ?? false)
                ->form([
                    Textarea::make('reason')->label('Motif du remboursement')->required(),
                ])
                ->action(fn (Payment $record, array $data) => self::runAction(
                    $record,
                    fn (PaymentService $service) => $service->refund($record, auth()->user(), $data['reason'])
                )),
        ];
    }

    private static function runAction(Payment $record, \Closure $callback): void
    {
        try {
            $callback(app(PaymentService::class));

            Notification::make()
                ->title('Paiement mis à jour : '.$record->fresh()->statusLabel())
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
            TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
