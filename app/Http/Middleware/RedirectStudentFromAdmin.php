<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mencegah role "Siswa" mengakses area Backend/Admin (prefix /admin).
 *
 * Siswa memiliki portal sendiri (prefix /portal). Jika seorang Siswa
 * mencoba membuka URL /admin/* (baik diketik manual maupun akibat
 * redirect yang salah dari controller), middleware ini akan memantulkan
 * mereka ke dashboard portal siswa alih-alih menampilkan layout admin
 * atau halaman 403 yang membingungkan.
 */
class RedirectStudentFromAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && $user->hasRole('Siswa')) {
            // Untuk request AJAX/JSON beri respon yang jelas, bukan redirect HTML.
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak. Halaman ini khusus untuk staf sekolah.',
                ], 403);
            }

            return redirect()
                ->route('student.dashboard')
                ->with('error', 'Anda tidak memiliki akses ke area administrasi.');
        }

        return $next($request);
    }
}
