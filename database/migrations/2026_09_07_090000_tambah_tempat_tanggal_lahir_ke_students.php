<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tempat & tanggal lahir siswa.
 *
 * Dipakai pada berkas biodata dan kartu peserta ujian: identitas peserta CBT
 * lazim diverifikasi dengan tempat/tanggal lahir, bukan hanya NISN. Keduanya
 * nullable karena data siswa lama sudah ada tanpa kolom ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('birth_place')->nullable()->after('gender');
            $table->date('birth_date')->nullable()->after('birth_place');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['birth_place', 'birth_date']);
        });
    }
};
