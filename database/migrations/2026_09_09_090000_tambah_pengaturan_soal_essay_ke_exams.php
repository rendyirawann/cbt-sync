<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan soal aktif dipisah antara Pilihan Ganda dan Essay.
 *
 * question_selection & active_question_count yang sudah ada sekarang HANYA
 * untuk PG; dua kolom di sini menjadi pasangannya untuk Essay.
 *
 * Bawaan 'all' dipilih supaya ujian yang sudah ada berperilaku sama seperti
 * sebelumnya: seluruh essay diberikan ke semua siswa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'essay_selection')) {
                $table->string('essay_selection', 20)->default('all')->after('active_question_count');
            }
            if (! Schema::hasColumn('exams', 'active_essay_count')) {
                $table->unsignedInteger('active_essay_count')->nullable()->after('essay_selection');
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            foreach (['essay_selection', 'active_essay_count'] as $kolom) {
                if (Schema::hasColumn('exams', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }
};
