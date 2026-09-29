<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * region_id / department_id / establishment_id / student_id sont TOUS
     * nullable et mutuellement "exclusifs par convention" : un seul est
     * renseigné selon le niveau hiérarchique du compte (section 31.1) :
     *   - national  : aucun des quatre renseigné
     *   - régional  : region_id renseigné
     *   - départ.   : department_id renseigné
     *   - établiss. : establishment_id renseigné
     *   - candidat  : student_id renseigné
     * L'exclusivité est vérifiée applicativement (Service/Observer), pas en
     * contrainte SQL (une contrainte CHECK portable multi-SGBD serait plus
     * fragile qu'utile ici).
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 150)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone', 30)->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('establishment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();

            $table->rememberToken();
            $table->timestamps();

            $table->index(['establishment_id']);
            $table->index(['department_id']);
            $table->index(['region_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
