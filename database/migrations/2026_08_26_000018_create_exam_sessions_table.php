<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('label', 100); // ex: "BEPC session 2027"
            $table->date('registration_start');
            $table->date('registration_end');
            $table->date('exam_date_start')->nullable();
            $table->date('exam_date_end')->nullable();
            $table->enum('status', ['brouillon', 'ouverte', 'fermee', 'archivee'])->default('brouillon');
            $table->timestamps();

            $table->unique(['exam_type_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
    }
};
