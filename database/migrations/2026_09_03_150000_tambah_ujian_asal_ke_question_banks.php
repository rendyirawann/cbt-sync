<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak UJIAN asal setiap soal di Bank Soal Bersama.
 *
 * Bank ditampilkan berkelompok per ujian ("soal dari UTS Matematika Kelas X"),
 * dan saat menarik soal ke ujian lain guru memilih dulu ujian sumbernya, baru
 * mencentang soalnya — semua atau satu per satu.
 *
 * Judul ujian ikut disimpan sebagai potret (source_exam_title) supaya kelompok
 * tetap punya nama yang terbaca walau ujian aslinya nanti dihapus, sementara
 * source_exam_id di-null-kan oleh basis data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->uuid('source_exam_id')->nullable()->after('source_bank_id');
            $table->string('source_exam_title')->nullable()->after('source_exam_id');
            $table->foreign('source_exam_id')->references('id')->on('exams')->nullOnDelete();
            $table->index('source_exam_id');
        });

        // Backfill entri yang sudah ada: cocokkan teks soal + mapel + sekolah ke
        // soal ujian yang masih tersimpan. Entri yang tidak ketemu dibiarkan
        // kosong dan akan tampil sebagai kelompok "Tanpa ujian asal".
        DB::statement("
            UPDATE question_banks qb
               SET source_exam_id = padanan.exam_id,
                   source_exam_title = padanan.title
              FROM (
                    SELECT q.question_text,
                           ta.subject_id,
                           cr.school_id,
                           e.id AS exam_id,
                           e.title
                      FROM questions q
                      JOIN exams e ON e.id = q.exam_id
                      JOIN teaching_assignments ta ON ta.id = e.teaching_assignment_id
                      JOIN class_rooms cr ON cr.id = ta.class_room_id
                   ) AS padanan
             WHERE qb.source_exam_id IS NULL
               AND qb.question_text = padanan.question_text
               AND qb.subject_id = padanan.subject_id
               AND qb.school_id IS NOT DISTINCT FROM padanan.school_id
        ");
    }

    public function down(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropForeign(['source_exam_id']);
            $table->dropIndex(['source_exam_id']);
            $table->dropColumn(['source_exam_id', 'source_exam_title']);
        });
    }
};
