<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan berapa kali siswa keluar dari layar ujian tapi KEMBALI dalam tenggang
 * waktu (toleransi). Keluar sekejap — notifikasi, salah tekan, atau membuka
 * kamera untuk memotret jawaban — tidak langsung mengunci sesi, tetapi tetap
 * dicatat di sini supaya guru tahu siapa yang sering keluar. Setelah melewati
 * batas toleransi, keluar berikutnya langsung mengunci dan butuh PIN pengawas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->unsignedInteger('leave_count')->default(0)->after('lock_count');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn('leave_count');
        });
    }
};
