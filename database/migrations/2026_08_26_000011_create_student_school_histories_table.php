<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trace l'historique établissement/classe d'un élève à travers les
     * années scolaires (transferts, redoublements) — indépendant de l'état
     * "courant" stocké sur students.establishment_id / students.class_id.
     */
    public function up(): void
    {
        Schema::create('student_school_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('establishment_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['inscrit', 'transfere', 'redouble', 'abandon', 'diplome'])->default('inscrit');
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_school_histories');
    }
};
