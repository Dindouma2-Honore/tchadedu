<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    // --- Statuts du workflow multi-niveaux (section 6 du document
    // d'architecture, adapté aux 3 niveaux de contrôle du diagramme :
    // Établissement -> Départemental -> Régional -> National -> Inscrit) ---
    public const STATUS_BROUILLON = 'brouillon';

    public const STATUS_SOUMISE = 'soumise';

    public const STATUS_A_CORRIGER_DEPARTEMENT = 'a_corriger_departement';

    public const STATUS_VALIDEE_DEPARTEMENT = 'validee_departement';

    public const STATUS_A_CORRIGER_REGION = 'a_corriger_region';

    public const STATUS_VALIDEE_REGION = 'validee_region';

    public const STATUS_A_CORRIGER_NATIONAL = 'a_corriger_national';

    public const STATUS_INSCRIT = 'inscrit';

    public const STATUS_REJETEE = 'rejetee';

    public const STATUS_ANNULEE = 'annulee';

    /** Libellés affichables, dans l'ordre logique du workflow. */
    public const LABELS = [
        self::STATUS_BROUILLON => 'Brouillon',
        self::STATUS_SOUMISE => 'Soumise (contrôle départemental)',
        self::STATUS_A_CORRIGER_DEPARTEMENT => 'À corriger (retour départemental)',
        self::STATUS_VALIDEE_DEPARTEMENT => 'Validée département (contrôle régional)',
        self::STATUS_A_CORRIGER_REGION => 'À corriger (retour régional)',
        self::STATUS_VALIDEE_REGION => 'Validée région (contrôle national)',
        self::STATUS_A_CORRIGER_NATIONAL => 'À corriger (retour national)',
        self::STATUS_INSCRIT => 'Inscrit (validation nationale)',
        self::STATUS_REJETEE => 'Rejetée',
        self::STATUS_ANNULEE => 'Annulée',
    ];

    /**
     * Pour chaque statut correspondant à une étape de contrôle en attente
     * d'une décision, indique : le niveau administratif responsable de la
     * décision, le statut cible en cas d'approbation, et le statut cible
     * en cas de demande de correction. Sert de source unique de vérité
     * pour ApplicationWorkflowService et ApplicationPolicy.
     *
     * @var array<string, array{level: string, approve_to: string, correction_to: string}>
     */
    public const CONTROL_STEPS = [
        self::STATUS_SOUMISE => [
            'level' => 'departmental',
            'approve_to' => self::STATUS_VALIDEE_DEPARTEMENT,
            'correction_to' => self::STATUS_A_CORRIGER_DEPARTEMENT,
        ],
        self::STATUS_VALIDEE_DEPARTEMENT => [
            'level' => 'regional',
            'approve_to' => self::STATUS_VALIDEE_REGION,
            'correction_to' => self::STATUS_A_CORRIGER_REGION,
        ],
        self::STATUS_VALIDEE_REGION => [
            'level' => 'national',
            'approve_to' => self::STATUS_INSCRIT,
            'correction_to' => self::STATUS_A_CORRIGER_NATIONAL,
        ],
    ];

    /** Statut "en attente" vers lequel repart un dossier corrigé et resoumis par l'établissement. */
    public const RESUBMIT_TARGETS = [
        self::STATUS_A_CORRIGER_DEPARTEMENT => self::STATUS_SOUMISE,
        self::STATUS_A_CORRIGER_REGION => self::STATUS_VALIDEE_DEPARTEMENT,
        self::STATUS_A_CORRIGER_NATIONAL => self::STATUS_VALIDEE_REGION,
    ];

    public const TERMINAL_STATUSES = [
        self::STATUS_INSCRIT,
        self::STATUS_REJETEE,
        self::STATUS_ANNULEE,
    ];

    protected $fillable = [
        'student_id',
        'establishment_id',
        'exam_session_id',
        'status',
        'submitted_at',
        'validated_at',
        'validated_by',
        'rejection_reason',
    ];

    // 'reference' est volontairement absent de $fillable : il est
    // exclusivement généré par ApplicationObserver/ApplicationReferenceService,
    // jamais assignable en masse depuis une requête utilisateur.

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'validated_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }

    public function examSession(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class);
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ApplicationStatusHistory::class)->latest('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Vrai si les frais de cette candidature ont été réglés (paiement au statut "success"). */
    public function hasSuccessfulPayment(): bool
    {
        return $this->payments()->where('status', Payment::STATUS_SUCCESS)->exists();
    }

    /** Vrai si la session de cet examen exige un paiement (section 18 : "si le cadre réglementaire le permet"). */
    public function requiresPayment(): bool
    {
        return (float) ($this->examSession?->fee_amount ?? 0) > 0;
    }

    /** Niveau administratif ('departmental'|'regional'|'national') attendu pour décider à l'étape actuelle, ou null si le dossier n'est pas à une étape de contrôle. */
    public function requiredLevelForDecision(): ?string
    {
        return self::CONTROL_STEPS[$this->status]['level'] ?? null;
    }

    public function isAtControlStep(): bool
    {
        return array_key_exists($this->status, self::CONTROL_STEPS);
    }

    public function isAwaitingCorrection(): bool
    {
        return array_key_exists($this->status, self::RESUBMIT_TARGETS);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    public function statusLabel(): string
    {
        return self::LABELS[$this->status] ?? $this->status;
    }
}
