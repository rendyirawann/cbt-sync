<?php

namespace App\Support;

use App\Models\Student;

/**
 * Satu tempat untuk menjawab "data siswa ini sudah lengkap atau belum".
 *
 * Dipakai DUA pemakai yang harus selalu sepakat: badge di tabel Data Siswa dan
 * catatan "Perlu dilengkapi" pada ringkasan impor Excel. Sebelumnya daftarnya
 * ditulis inline di dalam import(), jadi begitu tabelnya ikut menampilkan
 * kelengkapan, keduanya bisa diam-diam berbeda isi.
 *
 * Yang masuk daftar ini adalah kolom yang TIDAK wajib saat menyimpan, tapi
 * berdampak nyata bila kosong: tiga yang pertama tercetak di Kartu Ujian, tiga
 * berikutnya menentukan penempatan peserta.
 */
class KelengkapanSiswa
{
    /** Kolom model => label yang ditampilkan. */
    public const KOLOM = [
        'birth_place' => 'Tempat Lahir',
        'birth_date'  => 'Tanggal Lahir',
        'gender'      => 'Gender',
        'proctor_id'  => 'ID Proktor',
        'room'        => 'Ruang',
        'wave_id'     => 'Gelombang',
    ];

    /**
     * Kunci baris Excel => label.
     *
     * Sama dengan KOLOM, kecuali gelombang: di model bernama wave_id, sementara
     * kolom Excel-nya bernama 'wave' (isinya NAMA gelombang, bukan id).
     */
    public static function kolomExcel(): array
    {
        $peta = self::KOLOM;
        $peta['wave'] = $peta['wave_id'];
        unset($peta['wave_id']);

        return $peta;
    }

    /** Label kolom yang masih kosong pada satu siswa. Kosong = sudah lengkap. */
    public static function kurang(Student $siswa): array
    {
        $kurang = [];
        foreach (self::KOLOM as $kolom => $label) {
            if (blank($siswa->$kolom)) {
                $kurang[] = $label;
            }
        }

        return $kurang;
    }

    public static function lengkap(Student $siswa): bool
    {
        return self::kurang($siswa) === [];
    }
}
