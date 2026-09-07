<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master Gelombang.
 *
 * Gelombang membagi peserta satu ujian ke beberapa giliran waktu (pagi/siang)
 * memakai ruang komputer yang sama. Dibuat global seperti `subjects` — bukan
 * per sekolah — karena penamaannya universal ("Gelombang 1", "Gelombang 2")
 * dan dipakai lintas sekolah pada kartu login peserta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waves', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            // Urutan tampil; dua gelombang bisa punya nama yang tidak terurut abjad.
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waves');
    }
};
