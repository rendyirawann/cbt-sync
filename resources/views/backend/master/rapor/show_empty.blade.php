@extends(auth()->user()->hasRole('Siswa') ? 'frontend.layout.app' : 'backend.layout.app')
@section('title', 'e-Rapor Belum Tersedia')

@section('content')
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">e-Rapor Hasil Belajar</h1>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-xxl">

        <div class="card shadow-sm border-0">
            <div class="card-body text-center py-20">
                <i class="ki-outline ki-profile-user fs-5x text-warning mb-5"></i>
                <h2 class="fw-extrabold text-gray-900 mb-2">e-Rapor Belum Tersedia</h2>
                <p class="text-gray-500 fs-6 max-w-600px mx-auto mb-8">
                    Siswa bernama <strong>{{ $student->user->name }}</strong> belum terdaftar secara aktif pada rombongan belajar/kelas di tahun ajaran aktif ini. Silakan hubungi admin sekolah untuk memasukkan siswa ke dalam kelas terlebih dahulu.
                </p>
                @if(!auth()->user()->hasRole('Siswa'))
                    <a href="{{ route('admin.rapor.index') }}" class="btn btn-primary fw-bold">
                        <i class="ki-outline ki-left fs-4 me-1"></i> Kembali ke e-Rapor
                    </a>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
