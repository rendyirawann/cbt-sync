<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            // PIN yang dibuat guru untuk membuka kunci sesi siswa saat terdeteksi keluar layar.
            $table->string('resume_pin', 10)->nullable();
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            // Saat terkunci: timer dijeda. locked_at = kapan mulai terkunci.
            $table->timestamp('locked_at')->nullable();
            // Akumulasi total detik jeda (untuk audit/perpanjangan ends_at).
            $table->integer('paused_seconds')->default(0);
            // Jumlah pelanggaran (keluar layar) — untuk laporan.
            $table->integer('lock_count')->default(0);
        });

        // Backfill sesi lama dengan PIN acak agar langsung bisa dipakai.
        foreach (DB::table('exam_sessions')->whereNull('resume_pin')->pluck('id') as $id) {
            DB::table('exam_sessions')->where('id', $id)
                ->update(['resume_pin' => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT)]);
        }
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn('resume_pin');
        });
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn(['locked_at', 'paused_seconds', 'lock_count']);
        });
    }
};
