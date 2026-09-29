<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicLevel;
use App\Models\AcademicYear;
use App\Models\Classe;
use App\Models\Establishment;
use Illuminate\Database\Seeder;

class ClasseSeeder extends Seeder
{
    public function run(): void
    {
        $anneeCourante = AcademicYear::query()->where('is_current', true)->firstOrFail();
        $troisieme = AcademicLevel::query()->where('name', '3ème')->firstOrFail();
        $terminale = AcademicLevel::query()->where('name', 'Terminale')->firstOrFail();

        $establishments = Establishment::query()->get();

        foreach ($establishments as $establishment) {
            Classe::query()->updateOrCreate(
                [
                    'establishment_id' => $establishment->id,
                    'academic_year_id' => $anneeCourante->id,
                    'name' => '3ème A',
                ],
                [
                    'academic_level_id' => $troisieme->id,
                    'capacity' => 60,
                ]
            );

            Classe::query()->updateOrCreate(
                [
                    'establishment_id' => $establishment->id,
                    'academic_year_id' => $anneeCourante->id,
                    'name' => 'Terminale D',
                ],
                [
                    'academic_level_id' => $terminale->id,
                    'capacity' => 50,
                ]
            );
        }
    }
}
