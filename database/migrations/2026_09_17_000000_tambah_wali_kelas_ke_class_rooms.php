<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wali kelas melekat pada KELAS, bukan pada mata pelajaran.
 *
 * Sebelum ini kolom "Wali Kelas" di rapor diisi nama guru mapel pertama pada
 * daftar nilai — kebetulan saja, dan berubah-ubah mengikuti urutan mapel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('class_rooms', 'homeroom_teacher_id')) {
            return;
        }

        Schema::table('class_rooms', function (Blueprint $table) {
            // nullOnDelete: guru yang dihapus tidak ikut menghapus kelasnya,
            // kolom wali kelasnya sekadar dikosongkan.
            $table->foreignUuid('homeroom_teacher_id')->nullable()->after('level')
                ->constrained('teachers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('class_rooms', 'homeroom_teacher_id')) {
            return;
        }

        Schema::table('class_rooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('homeroom_teacher_id');
        });
    }
};
