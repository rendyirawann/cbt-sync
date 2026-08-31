@extends(auth()->user()->hasRole('Siswa') ? 'frontend.layout.app' : 'backend.layout.app')
@section('title', 'Raport Hasil Ujian Siswa - ' . $student->user->name)

@section('content')
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Raport Hasil Ujian</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">Portal</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">Raport Hasil Ujian</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">Detail</li>
            </ul>
        </div>
        <div class="d-flex align-items-center gap-2 gap-lg-3">
            @if(!auth()->user()->hasRole('Siswa'))
                <a href="{{ route('admin.rapor.index', ['class_room_id' => $classRoom->id]) }}" class="btn btn-sm fw-bold btn-secondary">
                    <i class="ki-outline ki-left fs-4 me-1"></i> Kembali ke Kelas
                </a>
            @endif
            <a href="{{ route('admin.rapor.generate', $student->id) }}" target="_blank" class="btn btn-sm fw-bold btn-success">
                <i class="ki-outline ki-printer fs-4 me-1"></i> Generate Raport Hasil Ujian (Cetak)
            </a>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-xxl">

        {{-- ======== STUDENT DATA CARD ======== --}}
        <div class="card shadow-sm border-0 mb-10">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-5">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-60px symbol-circle me-5">
                        <img src="{{ $student->user->avatar_url }}" alt="Avatar">
                    </div>
                    <div class="d-flex flex-column">
                        <h2 class="text-gray-900 fw-extrabold mb-1">{{ $student->user->name }}</h2>
                        <div class="d-flex flex-wrap fw-semibold fs-7 text-gray-500 gap-x-5 gap-y-2">
                            <span><i class="ki-outline ki-profile-circle me-1"></i> NISN: {{ $student->nisn }}</span>
                            <span><i class="ki-outline ki-home-fill me-1"></i> Kelas: {{ $classRoom->name }}</span>
                            <span><i class="ki-outline ki-calendar-8 me-1"></i> Tahun Ajaran: {{ $academicYear->name }}</span>
                        </div>
                    </div>
                </div>
                <div class="d-flex flex-column text-md-end bg-light-primary rounded px-5 py-3 align-self-stretch align-self-md-auto">
                    <span class="text-primary fw-bold fs-9 text-uppercase">Sekolah</span>
                    <span class="fw-bold text-gray-800 fs-5">{{ $student->school->name ?? '-' }}</span>
                </div>
            </div>
        </div>

        {{-- ======== METRIC GRID ======== --}}
        <div class="row g-5 g-xl-10 mb-10">
            {{-- RATA-RATA NILAI --}}
            <div class="col-md-3">
                <div class="card card-flush bg-light-success border-0 h-100 p-6">
                    <div class="card-header p-0 border-0 bg-transparent mb-3">
                        <span class="fs-7 fw-bold text-gray-600 text-uppercase">Rata-rata Nilai</span>
                    </div>
                    <div class="card-body p-0 d-flex align-items-center">
                        <div class="symbol symbol-50px me-4">
                            <span class="symbol-label bg-success bg-opacity-10 text-success">
                                <i class="ki-outline ki-award fs-2x text-success"></i>
                            </span>
                        </div>
                        <div>
                            <span class="fs-2hx fw-extrabold text-gray-900 d-block">{{ $raporData['overall_average'] }}</span>
                            <span class="fs-7 text-gray-500 fw-semibold">Rata-rata kumulatif</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- PREDIKAT HURUF --}}
            <div class="col-md-3">
                <div class="card card-flush bg-light-primary border-0 h-100 p-6">
                    <div class="card-header p-0 border-0 bg-transparent mb-3">
                        <span class="fs-7 fw-bold text-gray-600 text-uppercase">Predikat Rapor</span>
                    </div>
                    <div class="card-body p-0 d-flex align-items-center">
                        <div class="symbol symbol-50px me-4">
                            <span class="symbol-label bg-primary bg-opacity-10 text-primary">
                                <i class="ki-outline ki-shield-search fs-2x text-primary"></i>
                            </span>
                        </div>
                        <div>
                            <span class="fs-2hx fw-extrabold text-gray-900 d-block">Mutu {{ $raporData['overall_grade'] }}</span>
                            <span class="fs-7 text-gray-500 fw-semibold">Predikat nilai</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RANKING --}}
            <div class="col-md-3">
                <div class="card card-flush bg-light-warning border-0 h-100 p-6">
                    <div class="card-header p-0 border-0 bg-transparent mb-3">
                        <span class="fs-7 fw-bold text-gray-600 text-uppercase">Peringkat Kelas</span>
                    </div>
                    <div class="card-body p-0 d-flex align-items-center">
                        <div class="symbol symbol-50px me-4">
                            <span class="symbol-label bg-warning bg-opacity-10 text-warning">
                                <i class="ki-outline ki-cup fs-2x text-warning"></i>
                            </span>
                        </div>
                        <div>
                            <span class="fs-2hx fw-extrabold text-gray-900 d-block">Rank {{ $raporData['rank'] }}</span>
                            <span class="fs-7 text-gray-500 fw-semibold">Dari {{ $raporData['total_students'] }} siswa</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TOTAL MAPEL --}}
            <div class="col-md-3">
                <div class="card card-flush bg-light-danger border-0 h-100 p-6">
                    <div class="card-header p-0 border-0 bg-transparent mb-3">
                        <span class="fs-7 fw-bold text-gray-600 text-uppercase">Mata Pelajaran</span>
                    </div>
                    <div class="card-body p-0 d-flex align-items-center">
                        <div class="symbol symbol-50px me-4">
                            <span class="symbol-label bg-danger bg-opacity-10 text-danger">
                                <i class="ki-outline ki-book-open fs-2x text-danger"></i>
                            </span>
                        </div>
                        <div>
                            <span class="fs-2hx fw-extrabold text-gray-900 d-block">{{ count($raporData['subjects']) }}</span>
                            <span class="fs-7 text-gray-500 fw-semibold">Mapel terdaftar</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ======== SUBJECT REPORT TABLE ======== --}}
        <div class="card shadow-sm border-0">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <h3 class="fw-bold text-gray-900">Rincian Hasil Belajar Per Mata Pelajaran</h3>
                </div>
            </div>
            <div class="card-body py-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                <th>Mata Pelajaran</th>
                                <th>Guru Pengampu</th>
                                <th class="text-center">Total Tugas</th>
                                <th class="text-center">Tugas Selesai</th>
                                <th class="text-center">Nilai Ujian (CBT)</th>
                                <th class="text-center">Rata-rata Nilai</th>
                                <th class="text-center">Predikat</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-600 fw-semibold">
                            @forelse($raporData['subjects'] as $sub)
                                <tr>
                                    <td class="fw-bold text-gray-900">{{ $sub['subject_name'] }}</td>
                                    <td>{{ $sub['teacher_name'] }}</td>
                                    <td class="text-center">{{ $sub['total_assignments'] }}</td>
                                    <td class="text-center">
                                        @if($sub['completed_assignments'] == $sub['total_assignments'] && $sub['total_assignments'] > 0)
                                            <span class="badge badge-light-success fw-bold">{{ $sub['completed_assignments'] }} / {{ $sub['total_assignments'] }}</span>
                                        @else
                                            <span class="badge badge-light fw-bold">{{ $sub['completed_assignments'] }} / {{ $sub['total_assignments'] }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if(!empty($sub['exam_count']))
                                            <span class="badge badge-light-info fw-bold">{{ $sub['exam_average'] }}</span>
                                            <span class="text-muted fs-8 d-block">{{ $sub['exam_count'] }}x ujian</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center fw-extrabold text-gray-900">
                                        {{ $sub['average_score'] }}
                                    </td>
                                    <td class="text-center">
                                        @if($sub['letter_grade'] === 'A')
                                            <span class="badge badge-light-success fw-bold fs-7 px-3 py-1">A</span>
                                        @elseif($sub['letter_grade'] === 'B')
                                            <span class="badge badge-light-primary fw-bold fs-7 px-3 py-1">B</span>
                                        @elseif($sub['letter_grade'] === 'C')
                                            <span class="badge badge-light-warning fw-bold fs-7 px-3 py-1">C</span>
                                        @elseif($sub['letter_grade'] === 'D')
                                            <span class="badge badge-light-danger fw-bold fs-7 px-3 py-1">D</span>
                                        @else
                                            <span class="badge badge-danger fw-bold fs-7 px-3 py-1">E</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-10 text-muted">Belum ada mata pelajaran terdaftar pada kelas ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
