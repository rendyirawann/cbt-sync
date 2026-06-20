<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_session_student', function (Blueprint $table) {
            // Pivot tanpa PK id (cukup unique komposit) agar belongsToMany sync()
            // tidak butuh generator UUID.
            $table->foreignUuid('exam_session_id')->constrained('exam_sessions')->onDelete('cascade');
            $table->foreignUuid('student_id')->constrained('students')->onDelete('cascade');
            $table->timestamps();

            $table->primary(['exam_session_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_session_student');
    }
};
