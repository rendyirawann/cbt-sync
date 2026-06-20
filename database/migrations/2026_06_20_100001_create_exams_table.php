<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('teaching_assignment_id')->constrained('teaching_assignments')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            // Kategori ujian: mixed (PG+Essay), mc (PG saja), essay (Essay saja)
            $table->enum('type', ['mixed', 'mc', 'essay'])->default('mc');
            // Mode pembagian nilai
            $table->enum('points_mode', ['per_question', 'equal', 'manual'])->default('per_question');
            $table->decimal('wrong_penalty', 6, 2)->default(0);   // pengurang default utk salah (mode equal)
            $table->boolean('normalize')->default(true);          // skala nilai akhir ke 0-100
            $table->decimal('pass_score', 6, 2)->default(75);     // KKM
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
