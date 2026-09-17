<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Wali kelas ditulis manual, bukan dipilih dari akun guru.
 *
 * Wali kelas tidak selalu punya akun di sistem, dan namanya pada rapor sering
 * ditulis lengkap dengan gelar — berbeda dari nama akun. Jadi kolomnya menjadi
 * teks bebas.
 *
 * Nama yang sudah terlanjur dipilih lewat dropdown disalin dulu ke kolom teks
 * sebelum kolom lama dibuang, supaya tidak ada isian yang hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('class_rooms', 'homeroom_teacher')) {
            Schema::table('class_rooms', function (Blueprint $table) {
                $table->string('homeroom_teacher', 150)->nullable()->after('level');
            });
        }

        if (Schema::hasColumn('class_rooms', 'homeroom_teacher_id')) {
            DB::statement("
                UPDATE class_rooms c
                SET homeroom_teacher = u.name
                FROM teachers t
                JOIN users u ON u.id = t.user_id
                WHERE c.homeroom_teacher_id = t.id
                  AND (c.homeroom_teacher IS NULL OR c.homeroom_teacher = '')
            ");

            Schema::table('class_rooms', function (Blueprint $table) {
                $table->dropConstrainedForeignId('homeroom_teacher_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('class_rooms', 'homeroom_teacher')) {
            Schema::table('class_rooms', function (Blueprint $table) {
                $table->dropColumn('homeroom_teacher');
            });
        }
    }
};
