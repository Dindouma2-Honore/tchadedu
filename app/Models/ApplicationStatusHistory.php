<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Table sans updated_at (entrée immuable une fois créée) : on désactive la
 * gestion Eloquent de UPDATED_AT plutôt que de laisser une colonne
 * inexistante provoquer une erreur SQL au premier save().
 */
class ApplicationStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'application_id',
        'from_status',
        'to_status',
        'changed_by',
        'comment',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
