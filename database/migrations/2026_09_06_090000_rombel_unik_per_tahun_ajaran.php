<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu siswa hanya boleh berada di SATU rombel per tahun ajaran.
 *
 * Kode plotting sudah memakai updateOrCreate berkunci (student_id, academic_year_id)
 * sehingga memindahkan kelas di tahun yang sama akan mengganti barisnya, bukan
 * menambah. Batasan ini menegakkan aturan itu di tingkat basis data agar tidak
 * bisa dilanggar lewat impor, seeder, atau penyuntingan langsung.
 *
 * Naik kelas TIDAK terhalang: tahun ajaran berbeda = baris baru, dan baris tahun
 * sebelumnya tetap tersimpan sebagai riwayat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_students', function (Blueprint $table) {
            $table->unique(['student_id', 'academic_year_id'], 'class_students_siswa_tahun_unik');
        });
    }

    public function down(): void
    {
        Schema::table('class_students', function (Blueprint $table) {
            $table->dropUnique('class_students_siswa_tahun_unik');
        });
    }
};
