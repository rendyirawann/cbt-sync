{{--
    Kartu Ujian pada My Profile siswa.

    Hanya untuk akun berperan Siswa yang punya profil siswa; kartu yang tampil
    adalah kartu MILIK akun yang sedang masuk (data diambil dari relasi, tidak
    pernah dari request). Halaman ini TIDAK menerbitkan password — kalau kartu
    belum pernah diterbitkan, baris Password dibiarkan kosong dan siswa diarahkan
    ke proktor/admin. Penerbitan hanya terjadi saat admin membuat akun atau
    mencetak kartu rombel.
--}}
@php
    $akun = auth()->user();
    $siswaSaya = $akun?->hasRole('Siswa') ? $akun->student : null;
@endphp

@if($siswaSaya)
    @php
        $siswaSaya->loadMissing('wave');
        $sandiSaya = \App\Support\KartuUjian::baca($siswaSaya);
        $tahunAktif = \App\Models\AcademicYear::where('is_active', 1)->first();
    @endphp

    <div class="card mb-5 mb-xl-10 shadow-sm border border-gray-300">
        <div class="card-header border-bottom border-gray-300">
            <div class="card-title m-0">
                <h3 class="fw-bold m-0">Kartu Ujian</h3>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('student.kartu-ujian.pdf') }}" class="btn btn-sm btn-light-danger">
                    <i class="ki-outline ki-file-down fs-5 me-1"></i>Unduh PDF
                </a>
            </div>
        </div>
        <div class="card-body">
            @if(!$sandiSaya)
                <div class="alert alert-light-warning fs-7 py-3">
                    Password kartu Anda belum diterbitkan, jadi baris <b>Password</b> masih kosong.
                    Mintalah proktor/admin sekolah mencetak kartu ujian rombel Anda.
                </div>
            @endif
            <div class="kartu-wrap" style="max-width:520px">
                @include('backend.master.class-rooms._kartu-isi', [
                    's' => $siswaSaya,
                    'sandi' => $sandiSaya,
                    'penyelenggara' => strtoupper($siswaSaya->school->name ?? 'SEKOLAH'),
                    'labelTahun' => $tahunAktif->name ?? '-',
                    'logo' => asset('assets/media/logos/tut-wuri-handayani.png'),
                    {{-- Di sini kartunya dirender BROWSER, jadi yang dipakai URL
                         (asset), bukan path berkas seperti pada versi PDF. --}}
                    'logoSekolah' => !empty($appSettings['site_logo'])
                        ? asset('assets/media/logos/' . $appSettings['site_logo'])
                        : null,
                ])
            </div>
            <div class="text-muted fs-8 mt-4">
                <i class="ki-outline ki-information-5 fs-6 text-primary me-1"></i>
                Username dan password di kartu ini yang dipakai untuk masuk ke portal ujian.
                Login juga bisa memakai email atau NISN. Jangan bagikan kartu ini ke orang lain.
            </div>
        </div>
    </div>
    @include('backend.master.class-rooms._kartu-gaya')
@endif
