<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `reference` est l'identifiant métier public, non devinable
     * (ex: BEPC-2027-TCD-00001234, généré applicativement), distinct de la
     * clé technique `id` — conforme à la section 9.2 du document.
     *
     * Statuts volontairement réduits pour le MVP (section 6 du document en
     * propose 12 ; on démarre avec le sous-ensemble strictement nécessaire
     * et on étend en Phase 2 quand les contrôles départementaux/régionaux
     * seront activés).
     */
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('establishment_id')->constrained()->restrictOnDelete();
            $table->foreignId('exam_session_id')->constrained()->restrictOnDelete();
            $table->enum('status', [
                'brouillon',
                'soumise',
                'a_corriger',
                'validee',
                'rejetee',
                'annulee',
            ])->default('brouillon');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'exam_session_id']);
            $table->index(['establishment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
