<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Simpan urutan soal & opsi (hasil acak) saat siswa MULAI ujian, agar urutan
 * tetap konsisten setiap kali soal dibuka/refresh — tidak teracak ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_attempts', 'layout')) {
                $table->text('layout')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn('layout');
        });
    }
};
