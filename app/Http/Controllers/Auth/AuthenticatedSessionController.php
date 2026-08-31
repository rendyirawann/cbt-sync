<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Jenssegers\Agent\Agent;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        // 1. JIKA SISWA mencoba login di /admin/login -> Tendang ke /login
        if ($user->hasRole('Siswa')) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Siswa silakan login melalui halaman khusus siswa.',
                ], 403);
            }
            return redirect()->route('student.login')->with('error', 'Siswa silakan login melalui halaman khusus siswa.');
        }

        // 2. Cek Status Banned
        if ($user->banned_at) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akun Anda telah dibekukan. Silakan hubungi admin.',
                ], 403);
            }
            throw ValidationException::withMessages([
                'email' => 'Akun Anda telah dibekukan.',
            ]);
        }

        // 3. Update Data User
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // 4. Log Activity
        if (function_exists('activity')) {
            $agent = new Agent;
            activity()
                ->useLog('login')
                ->causedBy($user)
                ->withProperties([
                    'ip' => $request->ip(),
                    'agent' => [
                        'browser' => $agent->browser(),
                        'os' => $agent->platform(),
                        'device' => $agent->device(),
                    ],
                ])
                ->log('Login berhasil melalui halaman Admin');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Login berhasil, mengalihkan...',
                'redirect' => route('dashboard')
            ], 200);
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $isStudent = $user && $user->hasRole('Siswa');
        $agent = new Agent;

        // Catat Log Logout
        if ($user && function_exists('activity')) {
            activity()
                ->useLog('logout')
                ->causedBy($user)
                ->withProperties([
                    'ip' => $request->ip(),
                    'agent' => [
                        'browser' => $agent->browser(),
                        'os' => $agent->platform(),
                    ],
                ])
                ->log('Logout berhasil');
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($isStudent) {
            return redirect()->route('student.login');
        }

        return redirect()->route('login');
    }
}
