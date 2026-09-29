<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSession extends Model
{
    protected $fillable = [
        'exam_type_id',
        'academic_year_id',
        'label',
        'registration_start',
        'registration_end',
        'exam_date_start',
        'exam_date_end',
        'fee_amount',
        'status',
        'last_reference_sequence',
    ];

    protected function casts(): array
    {
        return [
            'registration_start' => 'date',
            'registration_end' => 'date',
            'exam_date_start' => 'date',
            'exam_date_end' => 'date',
            'fee_amount' => 'decimal:2',
        ];
    }

    public function examType(): BelongsTo
    {
        return $this->belongsTo(ExamType::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
