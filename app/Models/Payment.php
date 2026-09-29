<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    use HasFactory;
    public const STATUS_INITIATED = 'initiated';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_REFUNDED = 'refunded';

    public const LABELS = [
        self::STATUS_INITIATED => 'Initié',
        self::STATUS_PENDING => 'En attente',
        self::STATUS_SUCCESS => 'Payé',
        self::STATUS_FAILED => 'Échoué',
        self::STATUS_EXPIRED => 'Expiré',
        self::STATUS_REFUNDED => 'Remboursé',
    ];

    /** Statuts définitifs : plus aucune transition possible une fois atteints. */
    public const TERMINAL_STATUSES = [
        self::STATUS_SUCCESS,
        self::STATUS_FAILED,
        self::STATUS_EXPIRED,
        self::STATUS_REFUNDED,
    ];

    protected $fillable = [
        'reference',
        'application_id',
        'initiated_by',
        'amount',
        'currency',
        'status',
        'provider',
        'provider_reference',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class)->latest('id');
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(Receipt::class);
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
