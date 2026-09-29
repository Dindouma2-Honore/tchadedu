<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Étend le workflow de candidature (initialement réduit au MVP, cf.
 * commentaire de 2026_08_26_000019_create_applications_table) aux 3
 * niveaux de contrôle hiérarchique décrits section 6 du document
 * d'architecture et sur le diagramme "Workflow de candidature et
 * validation" : Départemental -> Régional -> National -> Inscrit.
 *
 * Ancien statut -> nouveau statut :
 *   validee    -> inscrit                  (la validation nationale = inscription)
 *   a_corriger -> a_corriger_departement   (seul niveau de contrôle qui existait jusqu'ici)
 * Les autres statuts (brouillon, soumise, rejetee, annulee) sont inchangés.
 *
 * Approche en 2 temps, nécessaire car applications.status a une contrainte
 * ENUM/CHECK (mysql, pgsql *et* sqlite en imposent une pour un `enum()` de
 * migration) : on élargit d'abord la contrainte pour accepter à la fois les
 * anciennes et les nouvelles valeurs, on migre les données, puis on
 * resserre la contrainte sur la liste finale. `->change()` est utilisé
 * plutôt que du SQL brut pour rester portable entre les 3 SGBD supportés
 * par l'application (dev en MySQL, tests en SQLite, cible pgsql).
 */
return new class extends Migration
{
    private const OLD_STATUSES = ['brouillon', 'soumise', 'a_corriger', 'validee', 'rejetee', 'annulee'];

    private const NEW_STATUSES = [
        'brouillon',
        'soumise',
        'a_corriger_departement',
        'validee_departement',
        'a_corriger_region',
        'validee_region',
        'a_corriger_national',
        'inscrit',
        'rejetee',
        'annulee',
    ];

    public function up(): void
    {
        $this->applyEnum(array_values(array_unique([...self::OLD_STATUSES, ...self::NEW_STATUSES])));

        DB::table('applications')->where('status', 'validee')->update(['status' => 'inscrit']);
        DB::table('applications')->where('status', 'a_corriger')->update(['status' => 'a_corriger_departement']);

        $this->applyEnum(self::NEW_STATUSES);
    }

    public function down(): void
    {
        $this->applyEnum(array_values(array_unique([...self::OLD_STATUSES, ...self::NEW_STATUSES])));

        DB::table('applications')->where('status', 'inscrit')->update(['status' => 'validee']);
        DB::table('applications')->whereIn('status', [
            'a_corriger_departement', 'a_corriger_region', 'a_corriger_national',
        ])->update(['status' => 'a_corriger']);
        DB::table('applications')->whereIn('status', [
            'validee_departement', 'validee_region',
        ])->update(['status' => 'soumise']);

        $this->applyEnum(self::OLD_STATUSES);
    }

    /** @param string[] $statuses */
    private function applyEnum(array $statuses): void
    {
        Schema::table('applications', function (Blueprint $table) use ($statuses) {
            $table->enum('status', $statuses)->default('brouillon')->change();
        });
    }
};
