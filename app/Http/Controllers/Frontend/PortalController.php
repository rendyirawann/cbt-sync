<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClassStudent;
use App\Models\TeachingAssignment;
use App\Models\LearningModule;
use App\Models\Assignment;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PortalController extends Controller
{
    public function login()
    {
        if (Auth::check()) {
            if (Auth::user()->hasRole('Siswa')) {
                return redirect()->route('student.dashboard');
            }
            return redirect()->route('dashboard');
        }
        return view('frontend.auth.login');
    }

    /**
     * Login siswa menerima TIGA bentuk identitas: email, username, atau NISN.
     * Kartu login peserta mencetak username, sementara banyak sekolah terbiasa
     * memakai NISN, jadi memaksa email membuat kartu itu tidak bisa dipakai.
     *
     * Field lama bernama `email`; nama itu tetap diterima agar form/klien lama
     * tidak putus, tapi isinya tidak lagi divalidasi sebagai email.
     */
    public function authenticate(Request $request)
    {
        $request->merge(['login' => trim((string) ($request->input('login') ?? $request->input('email')))]);

        $request->validate([
            'login' => 'required|string|max:255',
            'password' => 'required',
        ], [], ['login' => 'Email / Username / NISN']);

        if (Auth::attempt($this->kredensial($request->input('login'), $request->password), $request->filled('remember'))) {
            $user = Auth::user();
            
            // JIKA BUKAN SISWA -> TENDANG
            if (!$user->hasRole('Siswa')) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses Ditolak! Akun ini bukan akun siswa.',
                ], 403);
            }

            $request->session()->regenerate();

            return response()->json([
                'status' => 'success',
                'message' => 'Login Berhasil! Selamat datang, ' . $user->name,
                'redirect' => route('student.dashboard')
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Email/Username/NISN atau Password salah!',
        ], 401);
    }

    /**
     * Menentukan kolom mana yang dipakai untuk mencari akun.
     *
     * Berisi "@" -> email. Selain itu dicoba username; kalau tidak ada akun
     * dengan username itu, nilainya dianggap NISN dan ditukar ke email pemilik
     * NISN tersebut. Pencarian NISN memakai tabel students, bukan users, karena
     * NISN tidak disimpan di users.
     */
    private function kredensial(string $identitas, string $password): array
    {
        if (str_contains($identitas, '@')) {
            return ['email' => $identitas, 'password' => $password];
        }

        if (\App\Models\User::where('username', $identitas)->exists()) {
            return ['username' => $identitas, 'password' => $password];
        }

        $email = \App\Models\Student::where('nisn', $identitas)
            ->join('users', 'users.id', '=', 'students.user_id')
            ->value('users.email');

        // Tidak ketemu di mana pun: tetap kembalikan sebagai username supaya
        // Auth::attempt gagal wajar (bukan melempar galat).
        return $email
            ? ['email' => $email, 'password' => $password]
            : ['username' => $identitas, 'password' => $password];
    }


    /**
     * Kartu ujian MILIK siswa yang sedang masuk, sebagai PDF satu kartu.
     *
     * GET (tanpa efek samping): tidak menerbitkan password. Kalau kartunya belum
     * pernah diterbitkan, baris Password kosong — penerbitan tetap wewenang
     * admin/proktor lewat cetak kartu rombel.
     */
    public function kartuUjianPdf()
    {
        $siswa = auth()->user()->student;
        abort_unless($siswa, 404, 'Profil siswa tidak ditemukan.');

        $siswa->loadMissing(['user', 'wave', 'school']);
        $tahun = \App\Models\AcademicYear::where('is_active', 1)->first();

        $html = view('backend.master.class-rooms.cards', [
            'classRoom' => (object) ['school' => $siswa->school],
            'tahun' => $tahun,
            'peserta' => collect([$siswa]),
            'sandi' => [$siswa->id => \App\Support\KartuUjian::baca($siswa)],
            'logo' => public_path('assets/media/logos/tut-wuri-handayani.png'),
        ])->render();

        $options = new \Dompdf\Options();
        $options->set('chroot', public_path());
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');
        $options->set('dpi', 96);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $nama = 'Kartu-Ujian-' . \Illuminate\Support\Str::slug($siswa->user->name ?? 'siswa') . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $nama . '"',
        ]);
    }

    public function dashboard()
    {
        $user = Auth::user();
        if (!$user->hasRole('Siswa')) {
            return redirect()->route('dashboard');
        }

        $student = $user->student;

        // Akun berperan Siswa tapi belum punya baris students akan membuat
        // $student->id melempar galat. Sebelumnya tidak dijaga sama sekali.
        if (! $student) {
            return view('frontend.dashboard.index', ['cbt' => null, 'tahunAjaran' => null]);
        }

        // Rombel siswa DAN tahun ajarannya diambil bersama. Badge TA di beranda
        // dulu teks mati ("TA: 2023/2024 Ganjil") yang diketik di view, jadi
        // selalu salah begitu tahun ajarannya berganti.
        $rombel = ClassStudent::with('academicYear')
            ->where('student_id', $student->id)
            ->whereHas('academicYear', fn ($q) => $q->where('is_active', 1))
            ->first();

        // Beranda siswa hanya memuat hal yang berhubungan dengan CBT. Query modul,
        // tugas, absensi, dan kelas virtual dibuang bersama tampilannya — dulu
        // semuanya tetap dijalankan tiap kali halaman ini dibuka.
        $cbt = app(\App\Services\RingkasanCbt::class)->untukSiswa($student);

        return view('frontend.dashboard.index', [
            'cbt' => $cbt,
            'tahunAjaran' => $rombel?->academicYear,
        ]);
    }
}
