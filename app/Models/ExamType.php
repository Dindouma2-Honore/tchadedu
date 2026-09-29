<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamType extends Model
{
    protected $fillable = ['code', 'name', 'cycle'];

    public function examSessions(): HasMany
    {
        return $this->hasMany(ExamSession::class);
    }
}
