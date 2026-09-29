<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schéma standard du framework Laravel pour le canal de notification
 * "database" (cloche de notifications in-app affichée par Filament via
 * ->databaseNotifications()). Reproduit ici tel quel plutôt que généré via
 * `artisan notifications:table`, pour garder toutes les migrations du
 * projet explicites et versionnées de la même façon.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
