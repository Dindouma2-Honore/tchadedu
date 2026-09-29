<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Establishment;
use App\Models\Locality;
use App\Models\Region;
use Illuminate\Database\Seeder;

class TerritorySeeder extends Seeder
{
    /**
     * Un sous-ensemble réaliste (mais non exhaustif) des régions du Tchad,
     * suffisant pour tester l'isolation par périmètre (section 31) sur
     * plusieurs niveaux hiérarchiques distincts.
     */
    public function run(): void
    {
        $ndjamena = Region::query()->updateOrCreate(['code' => 'NDJ'], ['name' => "N'Djamena"]);
        $logoneOccidental = Region::query()->updateOrCreate(['code' => 'LO'], ['name' => 'Logone Occidental']);
        $ouaddai = Region::query()->updateOrCreate(['code' => 'OUA'], ['name' => 'Ouaddaï']);

        $deptNdjamena = Department::query()->updateOrCreate(
            ['code' => 'NDJ-01'],
            ['region_id' => $ndjamena->id, 'name' => "N'Djamena"]
        );

        $deptMoundou = Department::query()->updateOrCreate(
            ['code' => 'LO-01'],
            ['region_id' => $logoneOccidental->id, 'name' => 'Moundou']
        );

        $deptAbeche = Department::query()->updateOrCreate(
            ['code' => 'OUA-01'],
            ['region_id' => $ouaddai->id, 'name' => 'Abéché']
        );

        $localiteNdjamena = Locality::query()->updateOrCreate(
            ['code' => 'NDJ-01-01'],
            ['department_id' => $deptNdjamena->id, 'name' => 'Arrondissement 1']
        );

        $localiteMoundou = Locality::query()->updateOrCreate(
            ['code' => 'LO-01-01'],
            ['department_id' => $deptMoundou->id, 'name' => 'Moundou Centre']
        );

        $localiteAbeche = Locality::query()->updateOrCreate(
            ['code' => 'OUA-01-01'],
            ['department_id' => $deptAbeche->id, 'name' => 'Abéché Centre']
        );

        $establishments = [
            [
                'code' => 'NDJ-LYC-001',
                'name' => 'Lycée Félix Éboué',
                'type' => 'public',
                'region_id' => $ndjamena->id,
                'department_id' => $deptNdjamena->id,
                'locality_id' => $localiteNdjamena->id,
            ],
            [
                'code' => 'NDJ-COL-002',
                'name' => 'Collège Sacré-Cœur',
                'type' => 'prive',
                'region_id' => $ndjamena->id,
                'department_id' => $deptNdjamena->id,
                'locality_id' => $localiteNdjamena->id,
            ],
            [
                'code' => 'LO-LYC-001',
                'name' => 'Lycée de Moundou',
                'type' => 'public',
                'region_id' => $logoneOccidental->id,
                'department_id' => $deptMoundou->id,
                'locality_id' => $localiteMoundou->id,
            ],
            [
                'code' => 'LO-COL-002',
                'name' => 'Collège Notre-Dame de Moundou',
                'type' => 'prive',
                'region_id' => $logoneOccidental->id,
                'department_id' => $deptMoundou->id,
                'locality_id' => $localiteMoundou->id,
            ],
            [
                'code' => 'OUA-LYC-001',
                'name' => "Lycée d'Abéché",
                'type' => 'public',
                'region_id' => $ouaddai->id,
                'department_id' => $deptAbeche->id,
                'locality_id' => $localiteAbeche->id,
            ],
        ];

        foreach ($establishments as $establishment) {
            Establishment::query()->updateOrCreate(
                ['code' => $establishment['code']],
                [...$establishment, 'status' => 'actif']
            );
        }
    }
}
