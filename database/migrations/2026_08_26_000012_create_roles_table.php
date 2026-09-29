<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * scope_level indique à QUEL niveau de la hiérarchie (section 31.1) ce
     * rôle est censé opérer. Sert à valider en base qu'un rôle "regional"
     * n'est jamais attribué à un compte sans region_id (voir contrainte
     * applicative dans le Service d'attribution de rôle, pas en DB).
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique();
            $table->string('name', 100);
            $table->enum('scope_level', ['national', 'regional', 'departmental', 'establishment', 'self']);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
