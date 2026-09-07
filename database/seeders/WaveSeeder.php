<?php

namespace Database\Seeders;

use App\Models\Wave;
use Illuminate\Database\Seeder;

/**
 * Dua gelombang bawaan. Idempotent (firstOrCreate berkunci nama) supaya
 * seeder boleh dijalankan ulang tanpa menggandakan data.
 */
class WaveSeeder extends Seeder
{
    public function run(): void
    {
        // Jam bawaan mengikuti pola ANBK dua gelombang; sekolah bisa mengubahnya
        // di Data Master -> Master Gelombang.
        $bawaan = [
            ['Gelombang 1', 1, '07:30', '09:40'],
            ['Gelombang 2', 2, '10:30', '12:40'],
        ];

        foreach ($bawaan as [$nama, $urutan, $mulai, $selesai]) {
            Wave::firstOrCreate(
                ['name' => $nama],
                ['sort_order' => $urutan, 'is_active' => true,
                 'start_time' => $mulai, 'end_time' => $selesai]
            );
        }
    }
}
