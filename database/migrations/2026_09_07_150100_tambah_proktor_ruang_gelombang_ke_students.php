<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data pelaksanaan ujian yang menempel pada PESERTA, bukan pada ujian:
 * satu siswa duduk di ruang tertentu, diawasi proktor tertentu, pada gelombang
 * tertentu — dan ketiganya dicetak di kartu login peserta.
 *
 * `exam_password` menyimpan password kartu dalam bentuk TERENKRIPSI (Crypt,
 * kunci APP_KEY), bukan hash: kartu login harus bisa dicetak ulang dengan
 * password yang sama, sedangkan hash tidak bisa dibaca balik. Password yang
 * sama juga dipasang sebagai password akun (ter-hash) supaya kartunya benar
 * dapat dipakai login.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('proctor_id')->nullable()->after('nisn');
            $table->string('room')->nullable()->after('proctor_id');
            $table->foreignUuid('wave_id')->nullable()->after('room')
                ->constrained('waves')->nullOnDelete();
            $table->text('exam_password')->nullable()->after('wave_id');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('wave_id');
            $table->dropColumn(['proctor_id', 'room', 'exam_password']);
        });
    }
};
