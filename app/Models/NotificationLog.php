<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Une ligne par notification effectivement tentée, tous canaux confondus
 * (voir migration 2026_09_11_000002). Écrit exclusivement par
 * App\Notifications\Channels\SmsChannel et par les listeners du canal
 * mail/database (voir NotificationServiceProvider) — jamais manuellement.
 */
class NotificationLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'application_id',
        'channel',
        'subject',
        'body',
        'status',
        'error_message',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
