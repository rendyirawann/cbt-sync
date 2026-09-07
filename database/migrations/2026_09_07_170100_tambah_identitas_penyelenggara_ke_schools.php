<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identitas penyelenggara untuk kop Daftar Hadir Peserta.
 *
 * Lembar daftar hadir memuat KOTA/KABUPATEN + KODE dan SEKOLAH/MADRASAH + KODE.
 * Tanpa kolom ini keempat isian itu selalu kosong dan harus ditulis tangan tiap
 * kali mencetak. Semuanya nullable karena sekolah lama sudah ada tanpa data ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('city')->nullable()->after('address');
            $table->string('city_code', 20)->nullable()->after('city');
            $table->string('school_code', 20)->nullable()->after('city_code');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['city', 'city_code', 'school_code']);
        });
    }
};
