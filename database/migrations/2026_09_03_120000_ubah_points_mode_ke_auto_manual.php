<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menyelaraskan mode penilaian dengan versi LMS-SYNC.
 *
 * Sebelumnya cbt-sync mengenal tiga mode: per_question (poin tiap soal diisi guru),
 * equal (bagi rata), dan manual (guru menentukan nilai akhir). Mesin penilaian yang
 * baru hanya mengenal dua: auto (sistem membagi rata tiap bagian sehingga PG dan
 * Essay masing-masing bertotal 100) dan manual (guru mengisi nilai tiap essay).
 *
 * Pemetaan: equal -> auto, per_question -> manual, manual tetap manual.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Constraint lama HARUS dibuang lebih dulu: ia tidak mengizinkan nilai 'auto',
        // sehingga UPDATE ke 'auto' akan ditolak bila dijalankan sebelum ini.
        DB::statement('ALTER TABLE exams DROP CONSTRAINT IF EXISTS exams_points_mode_check');
        DB::statement("UPDATE exams SET points_mode = 'auto' WHERE points_mode = 'equal'");
        DB::statement("UPDATE exams SET points_mode = 'manual' WHERE points_mode = 'per_question'");
        DB::statement("ALTER TABLE exams ALTER COLUMN points_mode SET DEFAULT 'auto'");
        DB::statement("ALTER TABLE exams ADD CONSTRAINT exams_points_mode_check CHECK (points_mode IN ('auto', 'manual'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE exams DROP CONSTRAINT IF EXISTS exams_points_mode_check');
        DB::statement("ALTER TABLE exams ALTER COLUMN points_mode SET DEFAULT 'per_question'");
        DB::statement("ALTER TABLE exams ADD CONSTRAINT exams_points_mode_check CHECK (points_mode IN ('per_question', 'equal', 'manual'))");
    }
};
