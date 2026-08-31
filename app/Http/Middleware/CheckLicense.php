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
            $response = \Illuminate\Support\Facades\Http::timeout(5)->get('http://license-server.local/api/verify', [
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
