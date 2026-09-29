<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ExamType;
use Illuminate\Database\Seeder;

class ExamTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'BEPC', 'name' => "Brevet d'Études du Premier Cycle", 'cycle' => 'secondaire'],
            ['code' => 'BAC', 'name' => 'Baccalauréat', 'cycle' => 'secondaire'],
        ];

        foreach ($types as $type) {
            ExamType::query()->updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
