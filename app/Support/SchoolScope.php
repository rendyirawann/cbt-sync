<?php

namespace App\Support;

/**
 * Pembatasan data per-sekolah untuk CBT-SYNC.
 *
 * - Developer : role TERTINGGI (vendor/kita) — melihat & mengelola SEMUA sekolah &
 *   akun (tidak dibatasi). Tersembunyi, hanya dibuat lewat seeder.
 * - Superadmin / Admin / Guru / Kepala Sekolah : dibatasi ke sekolahnya (users.school_id),
 *   BILA diisi. Jika school_id user belum diisi → tidak dibatasi (fallback aman).
 *   (Superadmin = top admin per-sekolah; hanya mengelola sekolahnya, tidak ke sekolah lain.)
 */
class SchoolScope
{
    /** school_id pembatas, atau null bila tidak dibatasi. */
    public static function id(): ?string
    {
        $u = auth()->user();
        if (!$u) {
            return null;
        }
        if ($u->hasRole('Developer')) {
            return null;
        }
        return $u->school_id ?: null;
    }

    public static function active(): bool
    {
        return self::id() !== null;
    }
}
