<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jam pelaksanaan pada Master Gelombang.
 *
 * Jadwal ujian sekarang hanya berupa rentang TANGGAL (siswa boleh masuk kapan
 * saja di dalamnya), jadi jam pelaksanaan tidak lagi menempel pada jadwal
 * melainkan pada gelombang — dan dari situlah kolom "PUKUL" pada Daftar Hadir
 * Peserta diisi (mis. 07.30-09.40).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waves', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('name');
            $table->time('end_time')->nullable()->after('start_time');
        });
    }

    public function down(): void
    {
        Schema::table('waves', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
        });
    }
};
