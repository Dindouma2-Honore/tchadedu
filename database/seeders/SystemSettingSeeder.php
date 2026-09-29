<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'candidature.age_minimum', 'value' => 12, 'description' => "Âge minimum pour déposer une candidature (années)."],
            ['key' => 'notifications.sms_actif', 'value' => false, 'description' => "SMS désactivés en Phase 1 (prévu Phase 2)."],
            ['key' => 'plateforme.nom', 'value' => 'TCHAD EDU DIGITAL', 'description' => 'Nom affiché de la plateforme.'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::query()->updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
