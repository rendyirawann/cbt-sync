<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Cakupan Log Activity per peran.
 *
 * Aturannya BUKAN "siapa melihat apa saja" berbasis daftar id yang boleh
 * dilihat, melainkan "peran mana yang disembunyikan". Bentuk itu dipilih karena
 * daftar id yang boleh dilihat ikut membesar bersama jumlah user (ribuan siswa
 * berarti klausa IN berisi ribuan uuid), sedangkan daftar yang DISEMBUNYIKAN
 * selalu kecil — hanya beberapa akun Superadmin/Developer.
 *
 *  - Developer  : seluruh aktivitas, tanpa kecuali.
 *  - Superadmin : semua, KECUALI aktivitas akun ber-role Developer.
 *  - Admin      : semua, KECUALI Superadmin & Developer.
 *  - Guru       : hanya aktivitasnya sendiri.
 *  - Siswa/lain : hanya aktivitasnya sendiri.
 *
 * Aktivitas tanpa causer (dipicu sistem, mis. perintah terjadwal) ikut terlihat
 * oleh Admin ke atas — itu bukan aktivitas milik akun siapa pun, dan justru
 * bagian yang perlu terpantau. Guru & Siswa tidak melihatnya.
 */
class ActivityScope
{
    /** Peran yang aktivitasnya disembunyikan dari peran tertentu. */
    private const SEMBUNYI_DARI_SUPERADMIN = ['Developer'];
    private const SEMBUNYI_DARI_ADMIN = ['Developer', 'Superadmin', 'superadmin'];

    /** Tempelkan pembatasan ke query Activity. */
    public function terapkan(Builder $query, $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('Developer')) {
            return $query;
        }

        if ($user->hasRole(['Superadmin', 'superadmin'])) {
            return $this->kecualikanPeran($query, self::SEMBUNYI_DARI_SUPERADMIN);
        }

        if ($user->hasRole(['Admin', 'admin'])) {
            return $this->kecualikanPeran($query, self::SEMBUNYI_DARI_ADMIN);
        }

        // Guru, Siswa, dan peran lain: hanya miliknya sendiri.
        return $query->where('causer_id', $user->id);
    }

    /**
     * Buang aktivitas yang causer-nya punya salah satu peran tersebut.
     * Aktivitas tanpa causer tetap ikut (lihat catatan di kepala kelas).
     */
    private function kecualikanPeran(Builder $query, array $peran): Builder
    {
        $idTersembunyi = User::whereHas('roles', fn ($q) => $q->whereIn('name', $peran))
            ->pluck('id');

        if ($idTersembunyi->isEmpty()) {
            return $query;
        }

        return $query->where(fn ($q) => $q->whereNull('causer_id')
            ->orWhereNotIn('causer_id', $idTersembunyi));
    }

    /**
     * Bentuk lama (daftar id yang boleh dilihat) — dipertahankan agar pemanggil
     * lain tidak mendadak rusak. null berarti tanpa pembatasan.
     */
    public function visibleCauserIds($user): ?array
    {
        if (! $user) {
            return [];
        }
        if ($user->hasRole(['Developer', 'Superadmin', 'superadmin', 'Admin', 'admin'])) {
            return null;
        }

        return [$user->id];
    }
}
