<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom pendukung empat fitur ujian lanjutan.
 *
 * exams.question_selection + active_question_count
 *   Cara soal dipilih untuk siswa: 'all' (semua soal), 'manual' (hanya soal yang
 *   dicentang guru lewat questions.is_active), atau 'auto' (tiap siswa menerima
 *   sejumlah soal ACAK dari kolam yang aktif — mis. 30 acak dari 100).
 *
 * questions.is_active
 *   Penanda soal ikut diujikan atau tidak, tanpa perlu menghapus soalnya.
 *
 * exam_attempts.signature_path + confirmed_at
 *   Tanda tangan siswa dan waktu ia mengonfirmasi datanya sebelum masuk ujian.
 *   Adanya confirmed_at = siswa dinyatakan hadir dan mengikuti ujian.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->string('question_selection', 20)->default('all')->after('points_mode');
            $table->unsignedInteger('active_question_count')->nullable()->after('question_selection');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('penalty');
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('layout');
            $table->timestamp('confirmed_at')->nullable()->after('signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('exams', fn (Blueprint $t) => $t->dropColumn(['question_selection', 'active_question_count']));
        Schema::table('questions', fn (Blueprint $t) => $t->dropColumn('is_active'));
        Schema::table('exam_attempts', fn (Blueprint $t) => $t->dropColumn(['signature_path', 'confirmed_at']));
    }
};
