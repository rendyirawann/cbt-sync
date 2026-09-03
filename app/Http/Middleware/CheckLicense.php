<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Setting;

class CheckLicense
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Don't check on the license activation routes itself to prevent infinite loop
        if ($request->routeIs('license.*') || $request->is('license/*') || $request->is('license')) {
            return $next($request);
        }

        // Gerbang lisensi bisa dimatikan dari Pengaturan (khusus Superadmin).
        //
        // Bawaannya MATI, dan itu disengaja: sebelum ini middleware langsung
        // mengalihkan SETIAP halaman ke /license/activate begitu
        // Setting::get('app_license_key') kosong — yang persis terjadi di server
        // yang baru memasang pembaruan ini, karena tabel settings belum punya
        // baris lisensi sama sekali. Satu setelan yang belum diisi tidak boleh
        // menutup seluruh aplikasi.
        if (Setting::get('license_enabled', '0') !== '1') {
            return $next($request);
        }

        $licenseKey = Setting::get('app_license_key');

        // Check if the license is valid (for now, simply check if it equals the static key)
        if (!$this->isValidLicense($licenseKey)) {
            return redirect()->route('license.index');
        }

        return $next($request);
    }

    /**
     * Determine if the license is valid.
     */
    private function isValidLicense($key): bool
    {
        if (empty($key)) {
            return false;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get('https://dicotriyadi.site/license-server/api/verify', [
                'key' => $key
            ]);

            if ($response->successful() && $response->json('valid') === true) {
                return true;
            }
        } catch (\Exception $e) {
            // Jika internet down saat pengecekan rutin, 
            // aplikasi akan terkunci.
            return false;
        }

        return false;
    }
}
