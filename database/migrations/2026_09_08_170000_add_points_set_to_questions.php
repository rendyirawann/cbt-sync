<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda "bobot soal ini diisi guru".
 *
 * Sebelumnya bobot yang diisi guru dibedakan dari bawaan dengan menebak:
 * `points > 1` dianggap diisi guru, selain itu dibagi rata. Tebakan itu tidak
 * bisa dipakai untuk soal Pilihan Ganda, karena SEMUA soal lama sudah punya
 * points = 1 (nilai bawaan `$request->points ?: 1`) — jadi bobot 1 poin yang
 * memang disengaja tidak bisa dibedakan dari soal yang belum diatur.
 *
 * Kolom ini membuatnya eksplisit dan aman untuk data lama: soal yang sudah ada
 * tetap false, sehingga bobotnya tetap dibagi rata seperti sekarang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->boolean('points_set')->default(false)->after('points');
        });

        // Soal essay yang bobotnya sudah pernah diisi guru lewat cara lama
        // (points > 1) dipertahankan artinya, supaya nilai yang sudah berjalan
        // tidak berubah setelah migrasi.
        \Illuminate\Support\Facades\DB::table('questions')
            ->where('type', 'essay')
            ->where('points', '>', 1)
            ->update(['points_set' => true]);
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('points_set');
        });
    }
};
