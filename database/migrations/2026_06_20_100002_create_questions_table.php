<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('exam_id')->constrained('exams')->onDelete('cascade');
            $table->enum('type', ['mc', 'essay'])->default('mc');
            $table->text('question_text');
            $table->string('image_path')->nullable();
            $table->decimal('points', 6, 2)->default(1);    // nilai bila benar / skor maksimal essay
            $table->decimal('penalty', 6, 2)->default(0);   // pengurang bila salah (PG)
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
