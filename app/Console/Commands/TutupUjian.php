<?php

namespace App\Console\Commands;

use App\Models\Exam;
use App\Support\SiklusUjian;
use Illuminate\Console\Command;

/**
 * Menutup ujian yang sudah tuntas (semua peserta mengerjakan DAN tenggat
 * jadwalnya terlewat) menjadi berstatus SELESAI.
 *
 * Aplikasi juga menutupnya secara otomatis saat daftar ujian dibuka, jadi
 * perintah ini opsional — gunanya supaya perpindahan status tetap terjadi
 * walau tidak ada yang membuka halaman, mis. lewat cron harian:
 *
 *   php artisan ujian:tutup
 */
class TutupUjian extends Command
{
    protected $signature = 'ujian:tutup';

    protected $description = 'Tandai ujian yang sudah tuntas menjadi Selesai (status finished)';

    public function handle(): int
    {
        $kandidat = Exam::with(['sessions.students', 'sessions.attempts', 'teachingAssignment'])
            ->where('status', SiklusUjian::TERSEDIA)
            ->get();

        $this->info("Memeriksa {$kandidat->count()} ujian berstatus Available…");
        $jumlah = SiklusUjian::segarkan($kandidat);

        $this->info($jumlah > 0
            ? "$jumlah ujian ditandai Selesai."
            : 'Tidak ada ujian yang perlu ditutup.');

        return self::SUCCESS;
    }
}
