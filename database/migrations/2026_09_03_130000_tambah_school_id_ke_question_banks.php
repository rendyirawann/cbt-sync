<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bank Soal Bersama memuat soal dari SEMUA sekolah, jadi tiap entri perlu tahu
 * sekolah asalnya agar bisa difilter ("tampilkan soal buatan sekolah X").
 *
 * Sekolah asal diambil dari ruang kelas ujian tempat soal itu pertama dibuat,
 * bukan dari users.school_id pembuatnya — supaya tidak berubah bila akun guru
 * dipindahkan ke sekolah lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->uuid('school_id')->nullable()->after('subject_id');
            $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            $table->index(['school_id', 'subject_id', 'level'], 'question_banks_asal_index');
        });
    }

    public function down(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropIndex('question_banks_asal_index');
            $table->dropColumn('school_id');
        });
    }
};
