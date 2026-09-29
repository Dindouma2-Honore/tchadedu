<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Section 18 du document : "Si des frais d'examen doivent être payés en
 * ligne, le paiement doit être traité comme une intégration indépendante."
 * Le paiement n'est donc pas systématique : il dépend de la session
 * (certaines sessions/examens peuvent rester gratuits). NULL ou 0 =
 * aucun paiement requis pour cette session.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->decimal('fee_amount', 10, 2)->nullable()->after('exam_date_end');
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn('fee_amount');
        });
    }
};
