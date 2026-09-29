<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DocumentType;
use App\Models\ExamType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $bac = ExamType::query()->where('code', 'BAC')->first();

        $types = [
            ['code' => 'acte_naissance', 'name' => 'Acte de naissance', 'exam_type_id' => null, 'is_required' => true],
            ['code' => 'certificat_scolarite', 'name' => 'Certificat de scolarité', 'exam_type_id' => null, 'is_required' => true],
            ['code' => 'photo_identite', 'name' => "Photo d'identité", 'exam_type_id' => null, 'is_required' => true],
            ['code' => 'certificat_medical', 'name' => 'Certificat médical', 'exam_type_id' => $bac?->id, 'is_required' => false],
        ];

        foreach ($types as $type) {
            DocumentType::query()->updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
