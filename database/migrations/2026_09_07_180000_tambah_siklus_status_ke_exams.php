<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Siklus hidup data ujian: draft -> published -> finished -> history.
 *
 *  draft      : belum terbit, hanya penyusunnya yang melihat.
 *  published  : "Available" — tampil di Admin, Guru, dan Siswa.
 *  finished   : SELESAI otomatis ketika semua peserta sudah mengerjakan DAN
 *               tenggat jadwalnya terlewat. Hilang dari Admin/Guru/Siswa;
 *               hanya Superadmin & Developer yang masih bisa membukanya.
 *  history    : diarsipkan manual oleh Superadmin/Developer. Tampil kembali di
 *               Admin & Guru, tetapi tanpa tab Hasil dan Jadwal.
 *
 * Datanya TIDAK pernah dihapus — "reset" yang diminta sekolah dicapai dengan
 * menyembunyikan ujian yang sudah selesai dari layar sehari-hari, sementara
 * riwayat (attempt, jawaban, nilai) dan soal di Bank Soal tetap utuh.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Constraint lama hanya mengizinkan draft/published, jadi harus dibuang
        // sebelum nilai baru boleh masuk (urutan ini penting di PostgreSQL).
        DB::statement('ALTER TABLE exams DROP CONSTRAINT IF EXISTS exams_status_check');
        DB::statement("ALTER TABLE exams ADD CONSTRAINT exams_status_check CHECK (status IN ('draft', 'published', 'finished', 'history'))");

        Schema::table('exams', function (Blueprint $table) {
            // Jejak waktu perpindahan status — inilah "record history di layar
            // belakang": kapan ujian dinyatakan selesai dan kapan diarsipkan.
            $table->timestamp('finished_at')->nullable()->after('status');
            $table->timestamp('archived_at')->nullable()->after('finished_at');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn(['finished_at', 'archived_at']);
        });

        DB::statement("UPDATE exams SET status = 'published' WHERE status IN ('finished', 'history')");
        DB::statement('ALTER TABLE exams DROP CONSTRAINT IF EXISTS exams_status_check');
        DB::statement("ALTER TABLE exams ADD CONSTRAINT exams_status_check CHECK (status IN ('draft', 'published'))");
    }
};
