<?php

namespace App\Http\Controllers\Backend\Developer;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Onboarding sekolah baru (khusus Developer): buat SEKOLAH + AKUN ADMIN sekolahnya
 * (role Superadmin per-sekolah) dalam satu langkah. Tiap sekolah = satu lisensi.
 */
class OnboardingController extends Controller
{
    public function index()
    {
        $schools = School::withCount('users')->latest()->get();
        return view('backend.developer.onboarding', compact('schools'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'school_name' => 'required|string|max:255',
            'school_address' => 'nullable|string|max:1000',
            'school_phone' => 'nullable|string|max:50',
            'school_email' => 'nullable|email|max:255',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|unique:users,email',
            'admin_password' => 'required|string|min:6',
        ], [], [
            'school_name' => 'Nama Sekolah',
            'admin_name' => 'Nama Admin',
            'admin_email' => 'Email Admin',
            'admin_password' => 'Password Admin',
        ]);

        try {
            DB::transaction(function () use ($data) {
                $school = School::create([
                    'name' => $data['school_name'],
                    'address' => $data['school_address'] ?? null,
                    'phone' => $data['school_phone'] ?? null,
                    'email' => $data['school_email'] ?? null,
                ]);

                $user = User::create([
                    'name' => $data['admin_name'],
                    'email' => $data['admin_email'],
                    'username' => $data['admin_email'],
                    'school_id' => $school->id,      // admin terikat ke sekolahnya
                    'password' => Hash::make($data['admin_password']),
                    'email_verified_at' => now(),
                    'is_active' => 1,
                ]);
                // Akun teratas sekolah = Superadmin per-sekolah.
                $user->syncRoles([Role::firstOrCreate(['name' => 'Superadmin', 'guard_name' => 'web'])]);
            });

            return back()->with('success', 'Sekolah "' . $data['school_name'] . '" & akun admin (' . $data['admin_email'] . ') berhasil dibuat.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membuat sekolah: ' . $e->getMessage());
        }
    }
}
