<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Ordre strict : référentiels indépendants d'abord, puis tout ce qui
     * dépend d'eux par clé étrangère.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,

            AcademicYearSeeder::class,
            AcademicLevelSeeder::class,
            ExamTypeSeeder::class,
            DocumentTypeSeeder::class,

            TerritorySeeder::class,
            ClasseSeeder::class,
            StudentSeeder::class,
            UserSeeder::class,

            ExamSessionSeeder::class,
            ApplicationSeeder::class,

            SystemSettingSeeder::class,
        ]);
    }
}
