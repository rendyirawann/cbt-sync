<?php

namespace App\Support;

use App\Models\Student;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Password kartu login peserta.
 *
 * Password akun tersimpan sebagai hash yang tidak bisa dibaca balik, sedangkan
 * kartu login HARUS bisa dicetak ulang dengan password yang sama. Karena itu
 * password kartu disimpan terpisah di `students.exam_password` dalam bentuk
 * TERENKRIPSI (Crypt/APP_KEY) — bisa dibaca kembali oleh aplikasi, tapi tidak
 * terbaca langsung dari dump basis data.
 *
 * Password yang sama selalu dipasang sebagai password akun (ter-hash), jadi
 * password di kartu memang yang dipakai siswa untuk login.
 */
class KartuUjian
{
    /** Format mengikuti kartu ANBK: enam angka + tanda bintang, mis. 892777*. */
    public static function sandiBaru(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT) . '*';
    }

    /**
     * Password kartu siswa ini, atau null bila belum pernah diterbitkan.
     * APP_KEY yang berganti / data rusak dianggap "belum ada" supaya pemanggil
     * bisa menerbitkan ulang daripada gagal.
     */
    public static function baca(Student $s): ?string
    {
        if (blank($s->exam_password)) {
            return null;
        }

        try {
            return Crypt::decryptString($s->exam_password);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Terbitkan password kartu dan pasang sebagai password akun.
     * $sandi null = dibuatkan acak.
     */
    public static function terbitkan(Student $s, ?string $sandi = null): string
    {
        $sandi = $sandi ?: self::sandiBaru();

        DB::transaction(function () use ($s, $sandi) {
            $s->update(['exam_password' => Crypt::encryptString($sandi)]);
            if ($s->user) {
                $s->user->update(['password' => Hash::make($sandi)]);
            }
        });

        return $sandi;
    }

    /**
     * Password kartu yang siap dicetak: yang sudah ada dipakai apa adanya,
     * yang belum ada diterbitkan. Membuat cetak ulang menghasilkan password
     * yang sama dan tidak mengubah akun siswa yang sudah punya kartu.
     */
    public static function siapCetak(Student $s): string
    {
        return self::baca($s) ?? self::terbitkan($s);
    }
}
