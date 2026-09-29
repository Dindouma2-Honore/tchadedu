<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trace CHAQUE événement affectant un paiement (initiation, callback reçu
 * — y compris les callbacks dupliqués ou invalides —, confirmation
 * manuelle, remboursement). Conforme à la section 15 (traçabilité) et à la
 * ligne 174 : "Toute opération sensible doit produire un événement
 * d'audit [...] opération de paiement."
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('event', 30);
            $table->string('status_before', 20)->nullable();
            $table->string('status_after', 20)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
