<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sisa kolom foreign key yang belum berindeks.
 *
 * Empat kolom ini terlewat pada migrasi sebelumnya, dan tiga di antaranya ikut
 * terpicu saat menghapus siswa/akun (ON DELETE CASCADE): assignment_submissions,
 * attendances. role_has_permissions milik paket spatie dan tabelnya kecil, tapi
 * ikut diindeks agar pemeriksaan kolom FK tanpa indeks benar-benar bersih.
 */
return new class extends Migration
{
    private array $target = [
        ['assignment_submissions', 'assignment_id'],
        ['assignment_submissions', 'student_id'],
        ['assignments', 'teaching_assignment_id'],
        ['attendances', 'user_id'],
        ['role_has_permissions', 'role_id'],
    ];

    public function up(): void
    {
        foreach ($this->target as [$tabel, $kolom]) {
            if (! Schema::hasTable($tabel) || ! Schema::hasColumn($tabel, $kolom)) {
                continue;
            }
            $nama = 'idx_' . $tabel . '_' . $kolom;
            DB::statement("CREATE INDEX IF NOT EXISTS \"{$nama}\" ON \"{$tabel}\" (\"{$kolom}\")");
        }
    }

    public function down(): void
    {
        foreach ($this->target as [$tabel, $kolom]) {
            DB::statement('DROP INDEX IF EXISTS "idx_' . $tabel . '_' . $kolom . '"');
        }
    }
};
