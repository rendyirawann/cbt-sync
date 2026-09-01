<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Kepala Sekolah bersifat PEMANTAU (read-only): tidak boleh membuat/mengubah/menghapus
 * data. Aksi tulis (POST/PUT/PATCH/DELETE) diblokir, KECUALI layanan akun sendiri
 * (ganti password, profil, avatar, logout).
 */
class KepalaSekolahReadonly
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $isWrite = !in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);

        if ($user && $isWrite && $user->hasRole('Kepala Sekolah')) {
            $name = optional($request->route())->getName() ?? '';
            $selfService = Str::startsWith($name, ['my-', 'account', 'profile', 'security', 'avatar'])
                || in_array($name, ['logout', 'change.password'], true);

            if (!$selfService) {
                abort(403, 'Akun Kepala Sekolah bersifat pemantau (read-only) dan tidak dapat mengubah data.');
            }
        }

        return $next($request);
    }
}
