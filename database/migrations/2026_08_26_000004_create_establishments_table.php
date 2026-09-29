<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * region_id et department_id sont dénormalisés ici (en plus de locality_id)
     * pour permettre un filtrage par périmètre (section 31 du document
     * d'architecture) sans jointures profondes à chaque requête d'isolation.
     */
    public function up(): void
    {
        Schema::create('establishments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('locality_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name', 200);
            $table->enum('type', ['public', 'prive'])->default('public');
            $table->enum('status', ['actif', 'suspendu', 'ferme'])->default('actif');
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();

            $table->index(['region_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('establishments');
    }
};
