@extends('backend.layout.app')
@section('title', 'Raport Hasil Ujian Siswa')

@section('content')
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Raport Hasil Ujian</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">Administrasi</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">Akademik</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">Raport Hasil Ujian</li>
            </ul>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-xxl">

        {{-- ======== ALERTS ======== --}}
        @if(session('success'))
            <div class="alert alert-success d-flex align-items-center p-5 mb-10">
                <i class="ki-outline ki-shield-tick fs-2hx text-success me-4"></i>
                <div class="d-flex flex-column">
                    <h4 class="mb-1 text-dark fw-bold">Berhasil!</h4>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        {{-- ======== ROLES CONFIUGURATIONS TAB ======== --}}
        @if(auth()->user()->hasRole('Superadmin'))
            <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold mb-8">
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#kt_rapor_students">
                        Daftar Siswa & Kelas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_rapor_grade_settings">
                        Ketentuan Predikat Rapor
                    </a>
                </li>
            </ul>
        @endif

        <div class="tab-content">
            {{-- TAB 1: LIST STUDENTS --}}
            <div class="tab-pane fade show active" id="kt_rapor_students" role="tabpanel">
                <div class="card shadow-sm border-0 mb-10">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <span class="fs-4 fw-bold text-gray-900">Pilih Rombongan Belajar / Kelas</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('admin.rapor.index') }}" method="GET" class="d-flex gap-3 align-items-center max-w-500px">
                            <select name="class_room_id" class="form-select form-select-solid" data-control="select2" onchange="this.form.submit()">
                                <option value="">Pilih Ruang Kelas...</option>
                                @foreach($classRooms as $class)
                                    <option value="{{ $class->id }}" {{ $selectedClassId == $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>

                @if($selectedClassId)
                    <div class="card shadow-sm border-0">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title">
                                <h3 class="fw-bold text-gray-900">
                                    Daftar Siswa Kelas
                                </h3>
                            </div>
                        </div>
                        <div class="card-body py-4">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-5">
                                    <thead>
                                        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                            <th>Nama Lengkap</th>
                                            <th>NISN</th>
                                            <th>NIS</th>
                                            <th class="text-center">Gender</th>
                                            <th class="text-end pe-4">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-600 fw-semibold">
                                        @forelse($students as $student)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-35px symbol-circle me-3">
                                                            <img src="{{ $student->user->avatar_url }}" alt="">
                                                        </div>
                                                        <div class="d-flex flex-column">
                                                            <span class="text-gray-900 fw-bold">{{ $student->user->name }}</span>
                                                            <span class="text-gray-500 fs-8">{{ $student->user->email }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $student->nisn }}</td>
                                                <td>{{ $student->nis }}</td>
                                                <td class="text-center">
                                                    <span class="badge badge-light fw-bold">{{ $student->gender == 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <a href="{{ route('admin.rapor.show', $student->id) }}" class="btn btn-sm btn-light-primary fw-bold me-2">
                                                        <i class="ki-outline ki-eye fs-4 me-1"></i> Buka Rapor
                                                    </a>
                                                    <a href="{{ route('admin.rapor.generate', $student->id) }}" target="_blank" class="btn btn-sm btn-light-success fw-bold">
                                                        <i class="ki-outline ki-printer fs-4 me-1"></i> Cetak
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-10 text-muted">Belum ada siswa yang terdaftar di kelas ini.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="card border-dashed border-gray-300">
                        <div class="card-body text-center py-15">
                            <i class="ki-outline ki-award fs-5x text-gray-400 mb-5"></i>
                            <h3 class="fw-bold text-gray-900 mb-2">Pilih Kelas Terlebih Dahulu</h3>
                            <p class="text-gray-500 fs-6">Silakan pilih ruang kelas di atas untuk memuat daftar siswa dan memproses Raport Hasil Ujian.</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- TAB 2: GRADE THRESHOLDS SETTINGS --}}
            @if(auth()->user()->hasRole('Superadmin'))
                <div class="tab-pane fade" id="kt_rapor_grade_settings" role="tabpanel">
                    <div class="card shadow-sm border-0 max-w-800px">
                        <div class="card-header border-0 pt-6">
                            <h3 class="card-title fw-bold text-gray-900">Konfigurasi Nilai Huruf & Predikat Raport Hasil Ujian</h3>
                        </div>
                        <form action="{{ route('admin.rapor.settings') }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <p class="text-gray-500 mb-8 fs-6">
                                    Tentukan batas minimum nilai rata-rata bagi siswa untuk mendapatkan huruf mutu predikat (A, B, C, D). Nilai di bawah ambang batas D otomatis dikelompokkan ke dalam predikat E.
                                </p>

                                <div class="row g-9 mb-8">
                                    <div class="col-md-6">
                                        <label class="required fs-6 fw-bold mb-2">Batas Minimum Nilai A (Amat Baik)</label>
                                        <div class="input-group input-group-solid">
                                            <input type="number" name="grade_a" class="form-control" value="{{ $gradeA }}" min="1" max="100" step="0.1" required>
                                            <span class="input-group-text fw-bold">≥ SCORE</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="required fs-6 fw-bold mb-2">Batas Minimum Nilai B (Baik)</label>
                                        <div class="input-group input-group-solid">
                                            <input type="number" name="grade_b" class="form-control" value="{{ $gradeB }}" min="1" max="100" step="0.1" required>
                                            <span class="input-group-text fw-bold">≥ SCORE</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-9 mb-8">
                                    <div class="col-md-6">
                                        <label class="required fs-6 fw-bold mb-2">Batas Minimum Nilai C (Cukup)</label>
                                        <div class="input-group input-group-solid">
                                            <input type="number" name="grade_c" class="form-control" value="{{ $gradeC }}" min="1" max="100" step="0.1" required>
                                            <span class="input-group-text fw-bold">≥ SCORE</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="required fs-6 fw-bold mb-2">Batas Minimum Nilai D (Kurang)</label>
                                        <div class="input-group input-group-solid">
                                            <input type="number" name="grade_d" class="form-control" value="{{ $gradeD }}" min="1" max="100" step="0.1" required>
                                            <span class="input-group-text fw-bold">≥ SCORE</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-end bg-light bg-opacity-50 py-6 border-0">
                                <button type="submit" class="btn btn-primary fw-bold">
                                    Simpan Ketentuan Predikat
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
