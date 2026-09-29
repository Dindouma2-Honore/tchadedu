<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Nommé "Classe" (et non "Class", mot réservé PHP) — table associée:
 * "classes" (pluriel automatique de "Classe" via les conventions Eloquent).
 */
class Classe extends Model
{
    protected $fillable = [
        'establishment_id',
        'academic_level_id',
        'academic_year_id',
        'name',
        'capacity',
    ];

    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }

    public function academicLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicLevel::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }
}
