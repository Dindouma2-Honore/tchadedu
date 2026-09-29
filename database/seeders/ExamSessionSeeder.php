<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ExamSession;
use App\Models\ExamType;
use Illuminate\Database\Seeder;

class ExamSessionSeeder extends Seeder
{
    public function run(): void
    {
        $anneeCourante = AcademicYear::query()->where('is_current', true)->firstOrFail();
        $bepc = ExamType::query()->where('code', 'BEPC')->firstOrFail();
        $bac = ExamType::query()->where('code', 'BAC')->firstOrFail();

        ExamSession::query()->updateOrCreate(
            ['exam_type_id' => $bepc->id, 'academic_year_id' => $anneeCourante->id],
            [
                'label' => 'BEPC session 2027',
                'registration_start' => '2026-11-01',
                'registration_end' => '2027-01-31',
                'exam_date_start' => '2027-06-14',
                'exam_date_end' => '2027-06-16',
                'status' => 'ouverte',
            ]
        );

        ExamSession::query()->updateOrCreate(
            ['exam_type_id' => $bac->id, 'academic_year_id' => $anneeCourante->id],
            [
                'label' => 'Baccalauréat session 2027',
                'registration_start' => '2026-11-01',
                'registration_end' => '2027-01-31',
                'exam_date_start' => '2027-07-05',
                'exam_date_end' => '2027-07-10',
                'status' => 'ouverte',
            ]
        );
    }
}
