<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class LicenseController extends Controller
{
    /**
     * Tampilkan form aktivasi lisensi
     */
    public function index()
    {
        // Jika lisensi sudah valid, langsung redirect ke landing page atau dashboard
        $licenseKey = Setting::get('app_license_key');
        if ($this->isValidLicense($licenseKey)) {
            return redirect('/');
        }

        return view('license.activate');
    }

    /**
     * Proses aktivasi lisensi
     */
    public function activate(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string',
        ], [
            'license_key.required' => 'Kode lisensi wajib diisi.',
        ]);

        $key = $request->input('license_key');

        if ($this->isValidLicense($key)) {
            Setting::set('app_license_key', $key);
            
            return redirect('/')->with('success', 'Aplikasi berhasil diaktivasi!');
        }

        return back()->with('error', 'Kode lisensi tidak valid!');
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
            // Jika server lisensi down saat aktivasi, tolak aktivasi
            return false;
        }

        return false;
    }
}
