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
        //
    })->create();
