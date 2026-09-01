<?php

namespace App\Support;

/**
 * Pembatasan data per-sekolah untuk CBT-SYNC.
 *
 * - Superadmin : melihat & mengelola SEMUA sekolah (tidak dibatasi) — satu-satunya
 *   yang boleh menambah/mengubah master sekolah.
 * - Admin / Guru / Kepala Sekolah : dibatasi ke sekolahnya (users.school_id), BILA diisi.
 *   Jika school_id user belum diisi → tidak dibatasi (fallback aman, tidak memblokir).
 *   (Admin = admin sekolah: hanya mengelola datanya sendiri, tidak bisa ke sekolah lain.)
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
        if ($u->hasRole('Superadmin')) {
            return null;
        }
        return $u->school_id ?: null;
    }

    public static function active(): bool
    {
        return self::id() !== null;
    }
}
