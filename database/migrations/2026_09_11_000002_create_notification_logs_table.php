<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal de TOUTES les notifications envoyées, tous canaux confondus
 * (section 15 : traçabilité ; module "Notifications" du diagramme :
 * SMS / E-mail / Notifications internes / Alertes système).
 *
 * Distincte de la table `notifications` standard de Laravel, qui ne
 * stocke que le canal "database" : celle-ci trace aussi les envois mail et
 * SMS, y compris les échecs, ce que le framework ne fait pas nativement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('notifiable');
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('channel', ['mail', 'sms', 'database']);
            $table->string('subject')->nullable();
            $table->text('body');
            $table->enum('status', ['sent', 'failed'])->default('sent');
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['application_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
