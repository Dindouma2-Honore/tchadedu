<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `hash` (sha256 du fichier) permet de détecter une altération après
     * dépôt (section 10 du document). Pas d'antivirus/versioning au MVP —
     * prévu en Phase 2, mais la colonne `status` est déjà prête pour ça.
     */
    public function up(): void
    {
        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained()->restrictOnDelete();
            $table->string('file_path');
            $table->string('original_filename', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size'); // en octets
            $table->string('hash', 64)->nullable(); // sha256
            $table->enum('status', ['depose', 'verifie', 'rejete'])->default('depose');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['application_id', 'document_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
