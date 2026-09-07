<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda sesi susulan.
 *
 * Satu ujian sekarang hanya boleh punya SATU jadwal (rentang tanggal yang
 * dipakai semua gelombang). Sesi susulan tetap berupa baris exam_sessions
 * tambahan, jadi perlu penanda agar batas "satu jadwal" tidak ikut memblokirnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->boolean('is_makeup')->default(false)->after('name');
        });

        // Data lama: sesi susulan dibuat tanpa kelas dan namanya berawalan
        // "Susulan" (lihat modal Jadwalkan Susulan), jadi bisa dikenali dari itu.
        DB::table('exam_sessions')
            ->whereNull('class_room_id')
            ->where('name', 'like', 'Susulan%')
            ->update(['is_makeup' => true]);
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn('is_makeup');
        });
    }
};
