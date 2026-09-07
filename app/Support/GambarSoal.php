<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Mengecilkan gambar soal & opsi yang diunggah guru.
 *
 * MENGAPA perlu: halaman ujian merender SEMUA soal ke DOM sekaligus (berpindah
 * soal hanya mengubah `display`), dan browser tetap mengunduh <img src> di
 * dalam elemen display:none. Tanpa pengecilan, satu ujian 40 soal bergambar
 * 200 KB berarti ±8 MB per siswa per pemuatan halaman.
 *
 * MENGAPA sadar-format, bukan JPEG untuk semuanya:
 *   • PNG biasanya tangkapan layar rumus/diagram/tabel — teks tipis dan tepi
 *     tajam. Dipaksa JPEG q78, tepi huruf berbayang (selisih piksel terukur
 *     sampai 45/255). Jadi PNG DIPERTAHANKAN sebagai PNG: benar-benar lossless
 *     (selisih 0/255), yang dihemat hanya dari pengecilan dimensi.
 *   • JPEG biasanya foto kamera; q82 + maksimal 1600 px memangkas ±90% tanpa
 *     artefak yang terlihat pada foto.
 *
 * Berkas hanya ditulis ulang bila hasilnya BENAR-BENAR lebih kecil, sehingga
 * gambar yang sudah rapi tidak pernah diperburuk.
 */
class GambarSoal
{
    /** Sisi terpanjang maksimum. Cukup untuk proyektor & layar 1080p. */
    public const MAKS_SISI = 1600;

    /** Mutu JPEG. 82 dipilih lebih tinggi dari foto jawaban (78) karena gambar soal dilihat semua siswa. */
    public const MUTU_JPEG = 82;

    /**
     * Kecilkan gambar pada path disk 'public'. Aman dipanggil untuk path null.
     * Selalu mengembalikan path yang sama — nama berkas tidak berubah supaya
     * baris basis data tidak perlu disentuh.
     */
    public static function kecilkan(?string $path): ?string
    {
        if (blank($path)) {
            return $path;
        }

        try {
            $full = Storage::disk('public')->path($path);
            if (! is_file($full)) {
                return $path;
            }

            $info = @getimagesize($full);
            if (! $info) {
                return $path;          // bukan gambar yang bisa dibaca GD
            }

            [$w, $h] = $info;
            $mime = $info['mime'];
            $sebelum = filesize($full);

            $src = match ($mime) {
                'image/jpeg' => @imagecreatefromjpeg($full),
                'image/png'  => @imagecreatefrompng($full),
                default      => null,
            };
            if (! $src) {
                return $path;
            }

            // Skala: hanya diperkecil, tidak pernah diperbesar.
            $skala = min(1, self::MAKS_SISI / max($w, $h));
            $wBaru = max(1, (int) round($w * $skala));
            $hBaru = max(1, (int) round($h * $skala));

            if ($skala < 1) {
                $dst = imagecreatetruecolor($wBaru, $hBaru);
                if ($mime === 'image/png') {
                    // Transparansi diagram harus ikut terjaga.
                    imagealphablending($dst, false);
                    imagesavealpha($dst, true);
                    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
                }
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $wBaru, $hBaru, $w, $h);
                imagedestroy($src);
                $src = $dst;
            }

            // Tulis ke berkas sementara dulu: kalau hasilnya tidak lebih kecil,
            // berkas asli dibiarkan utuh.
            $tmp = $full . '.tmp';
            $ok = $mime === 'image/png'
                ? imagepng($src, $tmp, 9)                       // PNG: lossless
                : imagejpeg($src, $tmp, self::MUTU_JPEG);       // JPEG: mutu 82
            imagedestroy($src);

            if (! $ok || ! is_file($tmp)) {
                @unlink($tmp);
                return $path;
            }

            if (filesize($tmp) < $sebelum) {
                @rename($tmp, $full);
            } else {
                @unlink($tmp);
            }
        } catch (\Throwable $e) {
            // Gagal mengecilkan bukan alasan menggagalkan penyimpanan soal.
        }

        return $path;
    }
}
