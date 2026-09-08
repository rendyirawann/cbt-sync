<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Paksa HTTPS di Production/VPS agar tidak terjadi Mixed Content
        if (config('app.env') === 'production') {
            URL::forceRootUrl(config('app.url'));
            URL::forceScheme('https');
        }

        // Developer (vendor, tertinggi) & Superadmin (top admin sekolah) lolos semua
        // gate/izin FITUR. Isolasi DATA antar-sekolah ditangani terpisah oleh SchoolScope
        // (Superadmin tetap dibatasi ke sekolahnya; hanya Developer yang global).
        Gate::before(function ($user, $ability) {
            return $user->hasRole(['Developer', 'Superadmin', 'superadmin']) ? true : null;
        });

        // Rate limiter login: wajar (5x/menit per email+IP), melengkapi progressive-lockout di LoginRequest.
        RateLimiter::for('login', function ($request) {
            // Ember dibedakan per IDENTITAS + IP, bukan per IP saja.
            //
            // Ini penting sekali di hari ujian: satu sekolah keluar lewat SATU
            // IP publik (NAT), jadi kalau kuncinya hanya IP, siswa ke-6 dan
            // seterusnya kena 429 padahal passwordnya benar.
            //
            // Portal siswa mengirim field `login` (email/username/NISN),
            // sedangkan login admin mengirim `email`. Keduanya harus dibaca —
            // sebelumnya hanya `email`, sehingga sejak portal siswa memakai
            // `login` identitasnya selalu kosong dan seluruh siswa satu sekolah
            // berbagi satu ember 5/menit.
            $identitas = Str::lower((string) ($request->input('login') ?? $request->input('email')));

            return Limit::perMinute(5)->by($identitas . '|' . $request->ip());
        });

        // Setting dibagikan ke SEMUA view.
        //
        // Composer '*' berjalan untuk setiap view yang dirender — satu halaman
        // backend merender belasan partial. Dulu Schema::hasTable('settings')
        // ikut dipanggil setiap kali, dan itu BUKAN pemeriksaan gratis: tiap
        // panggilan menembak katalog PostgreSQL (pg_class/pg_namespace).
        // Terukur 10 query katalog per halaman, hanya untuk menanyakan hal yang
        // jawabannya tidak mungkin berubah di tengah permintaan.
        //
        // Sekarang jawabannya diingat dalam satu variabel per proses. Di Octane
        // proses hidup lama, jadi pemeriksaan itu praktis hanya sekali seumur
        // worker. Nilai settingnya sendiri sudah di-cache oleh Setting::allCached().
        $adaTabelSetting = null;
        $isiSetting = null;

        View::composer('*', function ($view) use (&$adaTabelSetting, &$isiSetting) {
            try {
                if ($adaTabelSetting === null) {
                    $adaTabelSetting = Schema::hasTable('settings');
                }
                if (! $adaTabelSetting) {
                    $view->with('appSettings', []);

                    return;
                }
                if ($isiSetting === null) {
                    $isiSetting = \App\Models\Setting::allCached();
                }
                $view->with('appSettings', $isiSetting);
            } catch (\Exception $e) {
                $view->with('appSettings', []);
            }
        });
    }
}
