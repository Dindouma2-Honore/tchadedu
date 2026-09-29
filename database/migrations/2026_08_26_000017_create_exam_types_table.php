<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // BEPC, BAC
            $table->string('name', 100);
            $table->enum('cycle', ['primaire', 'secondaire'])->default('secondaire');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_types');
    }
};
