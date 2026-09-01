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
            return Limit::perMinute(5)->by(Str::lower((string) $request->input('email')) . '|' . $request->ip());
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
