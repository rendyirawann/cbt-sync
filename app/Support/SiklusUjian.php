<?php

namespace App\Support;

use App\Models\Exam;
use App\Models\ClassStudent;
use App\Models\Student;
use Illuminate\Support\Collection;

/**
 * Siklus hidup data ujian dan siapa yang boleh melihatnya.
 *
 * Sekolah meminta data ujian "ter-reset" setiap ada ujian baru. Yang dilakukan
 * di sini BUKAN menghapus: ujian yang sudah tuntas berpindah status sehingga
 * hilang dari layar sehari-hari, sementara attempt, jawaban, nilai, dan soal di
 * Bank Soal tetap tersimpan sebagai riwayat.
 *
 *  draft      hanya penyusunnya.
 *  published  "Available" — Admin, Guru, Siswa.
 *  finished   SELESAI otomatis: semua peserta sudah mengerjakan DAN tenggat
 *             jadwal terlewat. Hanya Superadmin & Developer.
 *  history    diarsipkan manual oleh Superadmin/Developer: tampil lagi di Admin
 *             & Guru, tapi tab Hasil dan Jadwal disembunyikan dari mereka.
 */
class SiklusUjian
{
    public const DRAFT = 'draft';
    public const TERSEDIA = 'published';
    public const SELESAI = 'finished';
    public const RIWAYAT = 'history';

    /** Peran yang melihat SEMUA status, termasuk finished. */
    public const PENGAWAS = ['Superadmin', 'superadmin', 'Developer'];

    public static function labelStatus(string $status): string
    {
        return [
            self::DRAFT => 'Draft',
            self::TERSEDIA => 'Available',
            self::SELESAI => 'Selesai',
            self::RIWAYAT => 'History',
        ][$status] ?? $status;
    }

    public static function warnaStatus(string $status): string
    {
        return [
            self::DRAFT => 'warning',
            self::TERSEDIA => 'success',
            self::SELESAI => 'dark',
            self::RIWAYAT => 'info',
        ][$status] ?? 'secondary';
    }

    public static function pengawas($user = null): bool
    {
        $user = $user ?: auth()->user();

        return (bool) $user?->hasRole(self::PENGAWAS);
    }

    /** Status yang boleh dilihat peran user ini pada daftar & halaman ujian. */
    public static function statusTerlihat($user = null): array
    {
        if (self::pengawas($user)) {
            return [self::DRAFT, self::TERSEDIA, self::SELESAI, self::RIWAYAT];
        }

        // Admin & Guru: ujian SELESAI disembunyikan; yang sudah diarsipkan
        // (history) muncul kembali sebagai catatan.
        return [self::DRAFT, self::TERSEDIA, self::RIWAYAT];
    }

    public static function bolehLihat(Exam $exam, $user = null): bool
    {
        return in_array($exam->status, self::statusTerlihat($user), true);
    }

    /**
     * Tab Hasil & Jadwal hanya untuk yang berhak: pada ujian berstatus history,
     * Admin & Guru melihat ujiannya saja — hasil dan jadwalnya tidak.
     */
    public static function bolehLihatHasil(Exam $exam, $user = null): bool
    {
        return $exam->status !== self::RIWAYAT || self::pengawas($user);
    }

    /**
     * Apakah ujian ini sudah tuntas? Dua syarat harus terpenuhi bersamaan:
     *   1. tenggat jadwal (tanggal selesai terakhir) sudah terlewat, DAN
     *   2. tidak ada lagi peserta yang belum mengerjakan.
     *
     * Selama masih ada satu siswa yang belum mengerjakan, ujian tetap Available
     * walau tanggalnya lewat — supaya bisa disusulkan.
     */
    public static function sudahTuntas(Exam $exam): bool
    {
        $exam->loadMissing(['sessions.students', 'sessions.attempts', 'teachingAssignment']);

        if ($exam->sessions->isEmpty()) {
            return false;   // belum dijadwalkan → belum bisa dinyatakan selesai
        }

        $tenggat = $exam->sessions->max('ends_at');
        if (!$tenggat || now()->lessThanOrEqualTo($tenggat)) {
            return false;
        }

        return self::belumMengerjakan($exam)->isEmpty();
    }

    /**
     * Peserta yang sampai sekarang belum punya attempt pada ujian ini.
     * Peserta = daftar manual pada jadwal mana pun + anggota kelas untuk jadwal
     * bermode kelas (pada tahun ajaran ujian).
     */
    public static function belumMengerjakan(Exam $exam): Collection
    {
        $exam->loadMissing(['sessions.students', 'sessions.attempts', 'teachingAssignment']);

        $sudah = $exam->sessions->flatMap->attempts->pluck('student_id')->unique();

        $kelasIds = $exam->sessions->pluck('class_room_id')->filter()->unique();
        $tahunId = $exam->teachingAssignment?->academic_year_id;
        $dariKelas = $kelasIds->isEmpty() ? collect() : Student::whereIn(
            'id',
            ClassStudent::whereIn('class_room_id', $kelasIds)
                ->when($tahunId, fn ($q) => $q->where('academic_year_id', $tahunId))
                ->pluck('student_id')
        )->get();

        return $exam->sessions->flatMap->students
            ->concat($dariKelas)
            ->unique('id')
            ->reject(fn ($s) => $sudah->contains($s->id))
            ->values();
    }

    /**
     * Tandai ujian-ujian yang sudah tuntas menjadi SELESAI.
     * Dipanggil saat daftar/halaman ujian dibuka (kerjanya terbatas pada ujian
     * yang memang sedang dimuat) dan oleh perintah `ujian:tutup` untuk cron.
     *
     * @return int jumlah ujian yang berubah status
     */
    public static function segarkan($ujian): int
    {
        $ujian = $ujian instanceof Exam ? collect([$ujian]) : collect($ujian);
        $berubah = 0;

        foreach ($ujian as $exam) {
            if ($exam->status !== self::TERSEDIA) {
                continue;
            }
            if (! self::sudahTuntas($exam)) {
                continue;
            }

            $exam->update(['status' => self::SELESAI, 'finished_at' => now()]);
            $berubah++;
        }

        return $berubah;
    }
}
