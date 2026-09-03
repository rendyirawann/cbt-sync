<?php

namespace App\Http\Controllers\Backend\MyProfile;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Penyuntingan data sekolah MILIK AKUN SENDIRI dari halaman My Profile.
 *
 * Daftar sekolah (tambah/hapus/pindah) tetap milik Developer lewat Data Master.
 * Di sini Admin/Superadmin hanya boleh memperbarui isi sekolahnya sendiri.
 */
class SchoolProfileController extends Controller
{
    /** Peran yang boleh menyunting data sekolahnya sendiri. */
    public const BOLEH = ['Superadmin', 'superadmin', 'Admin', 'Developer'];

    public function update(Request $request)
    {
        $user = $request->user();

        // Diperiksa DI SINI, bukan hanya disembunyikan di tampilan: rute ini bisa
        // dipanggil langsung. school_id juga TIDAK pernah diambil dari request,
        // sehingga tidak ada cara menyunting sekolah milik orang lain.
        if (! $user?->hasRole(self::BOLEH) || ! $user->school_id) {
            return response()->json([
                'judul' => 'Ditolak',
                'error' => 'Akun Anda tidak berhak mengubah data sekolah.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:255',
            'address' => 'nullable|string|max:1000',
            'phone'   => 'nullable|string|max:30',
            'email'   => 'nullable|email|max:255',
        ], [
            'name.required' => 'Nama sekolah wajib diisi',
            'name.max'      => 'Nama sekolah maksimal 255 karakter',
            'email.email'   => 'Format email sekolah tidak valid',
            'phone.max'     => 'Telepon maksimal 30 karakter',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        $school = School::findOrFail($user->school_id);
        $school->fill($validator->validated())->save();

        return response()->json([
            'judul'   => 'Berhasil',
            'success' => 'Data sekolah berhasil diperbarui.',
        ]);
    }
}
