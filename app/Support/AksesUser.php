<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Aturan siapa boleh MELIHAT dan MENGELOLA akun di halaman User Management.
 *
 * Alasan berkas ini ada: sebelumnya daftar user hanya menyembunyikan akun
 * ber-role Developer, sehingga Admin sekolah masih melihat akun Superadmin —
 * padahal Superadmin adalah peran di atasnya dan keberadaannya tidak perlu
 * diketahui Admin. Menyembunyikan barisnya saja tidak cukup: seluruh endpoint
 * yang menerima id (detail, edit, update, hapus, ban) dulu memakai
 * findOrFail() tanpa pemeriksaan, jadi id yang ditebak tetap bisa diakses.
 * Semua pemeriksaan itu dikumpulkan di sini agar tidak ada jalur yang lupa.
 */
class AksesUser
{
    /** Role yang disembunyikan dari SEMUA orang (termasuk pengawas). */
    public const TERSEMBUNYI_SELALU = ['Developer'];

    /** Role yang hanya boleh dilihat pengawas (Superadmin & Developer). */
    public const HANYA_PENGAWAS = ['Superadmin', 'superadmin'];

    /** Role yang boleh mengelola akun lain selain pengawas. */
    public const PENGELOLA = ['Admin', 'admin'];

    /** Daftar nama role yang harus disembunyikan dari $pelihat. */
    public static function roleTersembunyi($pelihat = null): array
    {
        $pelihat = $pelihat ?: auth()->user();

        return SiklusUjian::pengawas($pelihat)
            ? self::TERSEMBUNYI_SELALU
            : array_merge(self::TERSEMBUNYI_SELALU, self::HANYA_PENGAWAS);
    }

    /** Tempelkan pembatasan role + sekolah ke query daftar user. */
    public static function saring($query, $pelihat = null)
    {
        $tersembunyi = self::roleTersembunyi($pelihat);
        $sekolah = SchoolScope::id();

        return $query
            ->whereDoesntHave('roles', fn ($q) => $q->whereIn('name', $tersembunyi))
            ->when($sekolah, fn ($q) => $q->where('school_id', $sekolah));
    }

    /** Bolehkah $pelihat melihat akun $target sama sekali. */
    public static function terlihat(User $target, $pelihat = null): bool
    {
        if ($target->hasRole(self::roleTersembunyi($pelihat))) {
            return false;
        }

        // Akun sekolah lain juga bukan urusannya, kecuali Developer
        // (SchoolScope::id() sudah mengembalikan null untuk Developer).
        $sekolah = SchoolScope::id();

        return $sekolah === null || $target->school_id === $sekolah;
    }

    /**
     * Ambil akun berdasarkan id, tetapi 404 bila $pelihat tidak boleh melihatnya.
     * Sengaja 404 (bukan 403) supaya keberadaan akun itu tidak terbongkar.
     */
    public static function ambilAtau404($id): User
    {
        $user = User::findOrFail($id);

        abort_unless(self::terlihat($user), 404);

        return $user;
    }

    /** Bolehkah $pelihat mengubah akun $target. */
    public static function bolehKelola(User $target, $pelihat = null): bool
    {
        $pelihat = $pelihat ?: auth()->user();

        if (!$pelihat || !self::terlihat($target, $pelihat)) {
            return false;
        }

        return SiklusUjian::pengawas($pelihat) || $pelihat->hasRole(self::PENGELOLA);
    }

    /**
     * Bolehkah $pelihat menghapus / mem-ban akun $target.
     * Akun sendiri dikecualikan agar tidak ada yang mengunci dirinya keluar.
     */
    public static function bolehTindakKeras(User $target, $pelihat = null): bool
    {
        $pelihat = $pelihat ?: auth()->user();

        return self::bolehKelola($target, $pelihat) && $target->id !== $pelihat->id;
    }

    /** Role yang boleh muncul di pemilih role pada form & filter. */
    public static function roleUntukForm($pelihat = null)
    {
        return Role::where('guard_name', 'web')
            ->whereNotIn('name', self::roleTersembunyi($pelihat))
            ->orderBy('id', 'desc')
            ->get();
    }
}
