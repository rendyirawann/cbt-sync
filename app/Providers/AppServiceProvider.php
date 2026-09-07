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

        // Share settings globally to all views
        View::composer('*', function ($view) {
            try {
                if (Schema::hasTable('settings')) {
                    $appSettings = \App\Models\Setting::allCached();
                    $view->with('appSettings', $appSettings);
                }
            } catch (\Exception $e) {
                $view->with('appSettings', []);
            }
        });
    }
}
