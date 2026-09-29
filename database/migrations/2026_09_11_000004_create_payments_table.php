<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Statuts repris tels que listés section 18 du document (INITIATED,
 * PENDING, SUCCESS, FAILED, EXPIRED, REFUNDED), en minuscules snake_case
 * pour rester cohérent avec le reste du schéma (statuts d'Application).
 *
 * `status` ne doit JAMAIS être mis à 'success' par une simple redirection
 * ou action côté client — uniquement par PaymentService::handleCallback()
 * (callback serveur-à-serveur signé) ou ::confirmManually() (réconciliation
 * humaine tracée). Voir app/Services/PaymentService.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('application_id')->constrained()->restrictOnDelete();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('XAF');
            $table->enum('status', ['initiated', 'pending', 'success', 'failed', 'expired', 'refunded'])->default('initiated');
            $table->string('provider', 50)->default('simulated');
            $table->string('provider_reference', 100)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['application_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
