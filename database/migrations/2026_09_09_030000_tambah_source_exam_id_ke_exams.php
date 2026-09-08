<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak asal untuk ujian hasil "Duplikat ke Kelas Lain".
 *
 * Dipakai untuk (a) menampilkan "Duplikat dari: PKN — X-1" di detail ujian, dan
 * (b) kelak menyediakan "Sinkronkan soal dari ujian sumber" selama duplikatnya
 * masih draft dan belum dikerjakan siapa pun.
 *
 * nullOnDelete: menghapus ujian sumber TIDAK boleh menghapus duplikatnya —
 * duplikat itu ujian milik kelas lain yang berdiri sendiri.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('exams', 'source_exam_id')) {
            return;
        }
        Schema::table('exams', function (Blueprint $table) {
            $table->uuid('source_exam_id')->nullable()->after('teaching_assignment_id');
            $table->foreign('source_exam_id')->references('id')->on('exams')->nullOnDelete();
            $table->index('source_exam_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('exams', 'source_exam_id')) {
            return;
        }
        Schema::table('exams', function (Blueprint $table) {
            $table->dropForeign(['source_exam_id']);
            $table->dropIndex(['source_exam_id']);
            $table->dropColumn('source_exam_id');
        });
    }
};
