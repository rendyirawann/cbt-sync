<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('exam_id')->constrained('exams')->onDelete('cascade');
            $table->string('name');
            // Opsional: target satu kelas (semua siswa aktif kelas itu). Null = pakai daftar peserta manual.
            $table->foreignUuid('class_room_id')->nullable()->constrained('class_rooms')->onDelete('set null');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->integer('duration_minutes')->default(60);
            $table->integer('max_capacity')->nullable();      // null = tanpa batas
            $table->boolean('shuffle_questions')->default(true);
            $table->boolean('shuffle_options')->default(true);
            $table->boolean('show_result')->default(true);    // siswa boleh lihat nilai setelah submit
            $table->enum('status', ['scheduled', 'closed'])->default('scheduled');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_sessions');
    }
};
