<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menambahkan header keamanan yang WAJAR (tidak terlalu ketat) ke setiap respons web:
 * anti clickjacking, anti MIME-sniffing, kebijakan referrer & fitur, dan HSTS (khusus HTTPS).
 * CSP dikirim sebagai Report-Only agar TIDAK memblokir apa pun (mencegah halaman putih),
 * cukup untuk memantau; bisa dipromosikan ke enforce nanti.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $h = $response->headers;

        $h->set('X-Content-Type-Options', 'nosniff');
        $h->set('X-Frame-Options', 'SAMEORIGIN');
        $h->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $h->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');
        $h->set('X-XSS-Protection', '0'); // auditor lama justru berisiko; CSP yang menangani

        // HSTS hanya bila HTTPS supaya dev http://127.0.0.1:8889 tidak terkunci.
        if ($request->secure()) {
            $h->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // CSP longgar & Report-Only (tidak memblokir). Mengizinkan semua sumber yang saat ini dipakai.
        $ct = (string) $h->get('Content-Type', '');
        if ($ct === '' || str_contains($ct, 'text/html')) {
            $csp = implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com https://app.midtrans.com https://app.sandbox.midtrans.com",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
                "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net",
                "img-src 'self' data: blob: https:",
                "connect-src 'self' ws: wss: https:",
                "frame-src 'self' https://app.midtrans.com https://app.sandbox.midtrans.com",
                "object-src 'none'",
                "base-uri 'self'",
                "frame-ancestors 'self'",
            ]);
            $h->set('Content-Security-Policy-Report-Only', $csp);
        }

        return $response;
    }
}
