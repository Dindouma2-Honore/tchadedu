<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicLevel;
use Illuminate\Database\Seeder;

class AcademicLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            ['name' => '6ème', 'cycle' => 'secondaire', 'order' => 1],
            ['name' => '5ème', 'cycle' => 'secondaire', 'order' => 2],
            ['name' => '4ème', 'cycle' => 'secondaire', 'order' => 3],
            ['name' => '3ème', 'cycle' => 'secondaire', 'order' => 4],
            ['name' => '2nde', 'cycle' => 'secondaire', 'order' => 5],
            ['name' => '1ère', 'cycle' => 'secondaire', 'order' => 6],
            ['name' => 'Terminale', 'cycle' => 'secondaire', 'order' => 7],
        ];

        foreach ($levels as $level) {
            AcademicLevel::query()->updateOrCreate(['name' => $level['name']], $level);
        }
    }
}
