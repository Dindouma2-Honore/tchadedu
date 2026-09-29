<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * matricule est nullable et attribué progressivement (après validation
     * d'une inscription), pas à la création du dossier élève.
     *
     * Pas de contrainte unique dure sur (last_name, first_name, birth_date) :
     * la détection de doublon (section 7 du document) reste un signalement
     * pour vérification humaine, jamais un blocage automatique.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('establishment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained()->nullOnDelete();
            $table->string('matricule', 30)->nullable()->unique();
            $table->string('last_name', 100);
            $table->string('first_name', 100);
            $table->date('birth_date');
            $table->string('birth_place', 150)->nullable();
            $table->enum('sex', ['M', 'F']);
            $table->string('nationality', 100)->default('Tchadienne');
            $table->string('photo_path')->nullable();
            $table->enum('status', ['actif', 'transfere', 'abandon', 'diplome'])->default('actif');
            $table->timestamps();

            $table->index(['last_name', 'first_name', 'birth_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
