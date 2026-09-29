<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        AcademicYear::query()->updateOrCreate(
            ['label' => '2025-2026'],
            ['start_date' => '2025-10-01', 'end_date' => '2026-07-31', 'is_current' => false]
        );

        AcademicYear::query()->updateOrCreate(
            ['label' => '2026-2027'],
            ['start_date' => '2026-10-01', 'end_date' => '2027-07-31', 'is_current' => true]
        );
    }
}
