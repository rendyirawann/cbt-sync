<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak asal soal yang DIPINJAM antar sekolah.
 *
 * Bila sekolah A menarik soal dari bank milik sekolah B, salinannya ikut masuk
 * bank sebagai milik sekolah A (school_id = A) — tetapi menyimpan sekolah
 * sumbernya (source_school_id = B) supaya bisa ditampilkan sebagai badge
 * "sumber: sekolah B" saat daftar difilter ke sekolah A.
 *
 * source_bank_id menyimpan entri bank aslinya untuk penelusuran; ia di-null-kan
 * bila entri asal dihapus, sedangkan nama sekolah sumber tetap terbaca.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->uuid('source_school_id')->nullable()->after('school_id');
            $table->uuid('source_bank_id')->nullable()->after('source_school_id');
            $table->foreign('source_school_id')->references('id')->on('schools')->nullOnDelete();
            $table->foreign('source_bank_id')->references('id')->on('question_banks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropForeign(['source_school_id']);
            $table->dropForeign(['source_bank_id']);
            $table->dropColumn(['source_school_id', 'source_bank_id']);
        });
    }
};
