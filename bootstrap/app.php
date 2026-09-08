<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // TLS diterminasi reverse-proxy di depan, lalu diteruskan ke nginx lokal
        // sebagai http. Tanpa mempercayai proxy, Laravel menganggap request tidak
        // aman: URL absolut jadi http:// dan cookie SESSION_SECURE_COOKIE tidak
        // pernah terkirim. Ada dua lapis proxy, jadi header dibaca apa adanya.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'forbid-banned-user' => \Cog\Laravel\Ban\Http\Middleware\ForbidBannedUser::class,
            'no-student' => \App\Http\Middleware\RedirectStudentFromAdmin::class,
            'kepsek.readonly' => \App\Http\Middleware\KepalaSekolahReadonly::class,
        ]);

        // 🔥 TAMBAHKAN BARIS INI (Agar logoutOtherDevices berfungsi)
        $middleware->web(append: [
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \App\Http\Middleware\CheckMaintenanceMode::class,
            \App\Http\Middleware\SecurityHeaders::class,
            \App\Http\Middleware\CheckLicense::class,
        ]);

        // 🔥 TAMBAHKAN KODE INI UNTUK MENGECUALIKAN WEBHOOK MIDTRANS DARI CSRF
        $middleware->validateCsrfTokens(except: [
            'api/midtrans-webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Token CSRF kedaluwarsa (419). Paling sering di halaman login yang
        // dibiarkan terbuka lama: sesi tamu habis, lalu tombol Masuk membalas
        // halaman galat 419 yang membingungkan — pengguna harus menekan Kembali
        // dan memuat ulang sendiri. Sekarang dialihkan kembali, sehingga token
        // barunya langsung terpasang dan bisa dicoba lagi seketika.
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            $pesan = 'Sesi keamanan kedaluwarsa karena halaman dibiarkan terbuka terlalu lama. '
                . 'Halaman sudah dimuat ulang — silakan coba lagi.';

            // Pemanggil AJAX (mis. form login siswa) menangani sendiri: kode 419
            // dipakai di sisi JS untuk memuat ulang halaman.
            if ($request->expectsJson()) {
                return response()->json(['message' => $pesan], 419);
            }

            // back() dipakai, BUKAN redirect ke URL permintaan: POST ke alamat
            // yang tidak punya route GET akan berbalas 404. Kata sandi tidak
            // pernah ikut dikembalikan.
            return redirect()->back()
                ->withInput($request->except(['password', 'password_confirmation', '_token']))
                ->with('error', $pesan);
        });
    })->create();
