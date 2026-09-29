<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    protected $fillable = [
        'establishment_id',
        'class_id',
        'matricule',
        'last_name',
        'first_name',
        'birth_date',
        'birth_place',
        'sex',
        'nationality',
        'photo_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'class_id');
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'student_guardian')
            ->withPivot(['relationship', 'is_primary_contact'])
            ->withTimestamps();
    }

    public function schoolHistories(): HasMany
    {
        return $this->hasMany(StudentSchoolHistory::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /** Compte candidat associé, si ce candidat a un accès en ligne. */
    public function userAccount(): HasOne
    {
        return $this->hasOne(User::class, 'student_id');
    }
}
