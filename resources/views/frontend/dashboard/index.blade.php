@extends('frontend.layout.app')
@section('title', 'Portal Siswa - Dashboard')

@section('content')
<style>
    /* Custom Styling for Student Portal */
    .student-welcome-card {
        background: linear-gradient(112.14deg, #2575fc 0%, #6a11cb 100%);
        border-radius: 20px;
        position: relative;
        overflow: hidden;
    }
    .student-welcome-img {
        position: absolute;
        right: 20px;
        bottom: -10px;
        height: 180px;
        opacity: 0.9;
    }
    .stat-card {
        border-radius: 15px;
        transition: all 0.3s ease;
        border: none !important;
    }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
    .course-card { border-radius: 15px; border: none; overflow: hidden; transition: all 0.3s ease; }
    .course-card:hover { transform: scale(1.02); box-shadow: 0 15px 30px rgba(0,0,0,0.1); }
    .nav-line-tabs .nav-item .nav-link.active, .nav-line-tabs .nav-item .nav-link:hover { border-bottom: 3px solid #6a11cb !important; color: #6a11cb !important; }
</style>

<!--begin::Toolbar-->
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Beranda Siswa</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">Portal</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">Dashboard</li>
            </ul>
        </div>
        <div class="d-flex align-items-center gap-2 gap-lg-3">
            <span class="badge badge-light-primary fw-bold px-4 py-3">TA: 2023/2024 Ganjil</span>
        </div>
    </div>
</div>

<div class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        
        <!-- Welcome Banner -->
        <div class="card student-welcome-card mb-10 border-0">
            <div class="card-body p-10 p-lg-15">
                <div class="d-flex flex-column">
                    <h1 class="text-white fw-bolder fs-2qx mb-3">Selamat Datang, {{ auth()->user()->name }}! 👋</h1>
                    <p class="text-white opacity-75 fs-5 mb-8 fw-semibold" style="max-width: 600px;">
                        Semangat belajar hari ini! Kamu memiliki <strong>{{ $stats['pending_assignments'] }} tugas</strong> yang belum dikerjakan. Ayo selesaikan tepat waktu!
                    </p>
                    <div class="d-flex gap-3">
                        <a href="{{ route('student.timetable') }}" class="btn btn-white fw-bold px-6">Lihat Jadwal</a>
                        <a href="{{ route('student.assignments.index') }}" class="btn btn-outline btn-outline-white fw-bold px-6">Kerjakan Tugas</a>
                    </div>
                </div>
                <img src="{{ URL::to('assets/media/illustrations/doofenshmirtz/2.png') }}" class="student-welcome-img d-none d-md-block" alt="">
            </div>
        </div>

        @if($activeLiveClass)
        <!-- Live Class Alert -->
        <div class="alert alert-dismissible bg-light-primary border border-primary border-dashed d-flex flex-column flex-sm-row p-5 mb-10">
            <i class="ki-outline ki-video fs-2hx text-primary me-4 mb-5 mb-sm-0"></i>
            <div class="d-flex flex-column pe-0 pe-sm-10">
                <h5 class="mb-1">Kelas Virtual Sedang Berlangsung!</h5>
                <span>Mata Pelajaran <strong>{{ $activeLiveClass->teachingAssignment->subject->name }}</strong> oleh <strong>{{ $activeLiveClass->teachingAssignment->teacher->user->name }}</strong> sedang berlangsung sekarang.</span>
            </div>
            <div class="ms-sm-auto">
                <a href="{{ $activeLiveClass->meeting_url }}" target="_blank" class="btn btn-primary fw-bold text-nowrap mt-3 mt-sm-0">
                    Masuk Kelas Virtual
                </a>
            </div>
        </div>
        @endif

        <!-- Attendance Status Bar -->
        <div class="row g-5 mb-10">
            <div class="col-12">
                <div class="card shadow-sm border-0 bg-white">
                    <div class="card-body d-flex flex-wrap align-items-center justify-content-between py-4">
                        <div class="d-flex align-items-center gap-3">
                            <i class="ki-outline ki-fingerprint fs-1 text-primary"></i>
                            <div>
                                <h4 class="mb-0 fw-bold">Status Absensi Hari Ini</h4>
                                <p class="text-muted fs-8 mb-0">{{ date('l, d F Y') }}</p>
                            </div>
                        </div>
                        <div class="d-flex gap-4">
                            <div class="d-flex align-items-center gap-2">
                                <span class="bullet bullet-vertical h-30px bg-{{ $stats['attendance_status']['datang'] ? 'success' : 'secondary' }}"></span>
                                <div class="d-flex flex-column">
                                    <span class="fs-8 fw-bold text-gray-400 text-uppercase">Absen Datang</span>
                                    <span class="fs-7 fw-bold text-{{ $stats['attendance_status']['datang'] ? 'success' : 'gray-400' }}">
                                        {{ $stats['attendance_status']['datang'] ? 'Sudah Dilakukan' : 'Belum Absen' }}
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="bullet bullet-vertical h-30px bg-{{ $stats['attendance_status']['pulang'] ? 'success' : 'secondary' }}"></span>
                                <div class="d-flex flex-column">
                                    <span class="fs-8 fw-bold text-gray-400 text-uppercase">Absen Pulang</span>
                                    <span class="fs-7 fw-bold text-{{ $stats['attendance_status']['pulang'] ? 'success' : 'gray-400' }}">
                                        {{ $stats['attendance_status']['pulang'] ? 'Sudah Dilakukan' : 'Belum Tersedia' }}
                                    </span>
                                </div>
                            </div>
                            <a href="{{ route('student.attendance') }}" class="btn btn-sm btn-light-primary fw-bold ms-lg-5">Portal Absensi</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row g-5 g-xl-10 mb-10">
            <div class="col-md-4">
                <div class="card stat-card bg-light-primary border-primary border-dashed">
                    <div class="card-body d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-primary"><i class="ki-outline ki-book-open fs-2x text-white"></i></span>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="text-gray-800 fw-bolder fs-3">{{ $stats['my_subjects'] }}</span>
                            <span class="text-gray-500 fw-bold fs-7">Mata Pelajaran</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-light-success border-success border-dashed">
                    <div class="card-body d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-success"><i class="ki-outline ki-file-sheet fs-2x text-white"></i></span>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="text-gray-800 fw-bolder fs-3">{{ $stats['new_modules'] }}</span>
                            <span class="text-gray-500 fw-bold fs-7">Modul Tersedia</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card bg-light-warning border-warning border-dashed">
                    <div class="card-body d-flex align-items-center">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-warning"><i class="ki-outline ki-notification-on fs-2x text-white"></i></span>
                        </div>
                        <div class="d-flex flex-column">
                            <span class="text-gray-800 fw-bolder fs-3">{{ $stats['pending_assignments'] }}</span>
                            <span class="text-gray-500 fw-bold fs-7">Tugas Menunggu</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-5 g-xl-10">
            <!-- Recent Modules -->
            <div class="col-xl-8">
                <div class="card shadow-sm border-0 mb-10">
                    <div class="card-header align-items-center border-0 mt-4">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="fw-bolder text-gray-900 fs-3">Modul Terbaru</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">Materi belajar terbaru dari bapak/ibu guru</span>
                        </h3>
                        <div class="card-toolbar">
                            <a href="{{ route('student.learning-modules.index') }}" class="btn btn-sm btn-light-primary fw-bold">Lihat Semua</a>
                        </div>
                    </div>
                    <div class="card-body pt-3">
                        @foreach($recentModules as $mod)
                        <div class="d-flex align-items-center mb-7 bg-light rounded p-5">
                            <div class="symbol symbol-50px me-5">
                                <span class="symbol-label bg-white shadow-sm">
                                    <i class="ki-outline ki-document fs-2x text-primary"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <a href="{{ route('student.learning-modules.index') }}" class="text-gray-900 fw-bolder text-hover-primary fs-6">{{ $mod->title }}</a>
                                <span class="text-muted d-block fw-semibold fs-7">{{ $mod->teachingAssignment->subject->name }} • {{ $mod->teachingAssignment->teacher->user->name }}</span>
                            </div>
                            <div class="badge badge-light-primary fw-bold">{{ $mod->formatted_file_size }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Side Activities / Announcements -->
            <div class="col-xl-4">
                <div class="card shadow-sm border-0 bg-light-dark bg-opacity-5 mb-10">
                    <div class="card-body p-8">
                        <h3 class="text-gray-900 fw-bolder mb-5">Pengumuman</h3>
                        <div class="timeline-label">
                            @forelse($announcements as $ann)
                            <div class="timeline-item">
                                <div class="timeline-label fw-bold text-gray-800 fs-7" style="width: 45px;">
                                    {{ $ann['time']->isToday() ? $ann['time']->format('H:i') : ($ann['time']->isYesterday() ? 'Kemarin' : $ann['time']->format('d M')) }}
                                </div>
                                <div class="timeline-badge">
                                    <i class="fa fa-genderless text-{{ $ann['color'] }} fs-1"></i>
                                </div>
                                <div class="timeline-content fw-semibold text-gray-600 ps-3">{{ $ann['title'] }}</div>
                            </div>
                            @empty
                            <div class="text-center py-5">
                                <span class="text-muted fs-7">Belum ada pengumuman terbaru.</span>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 overflow-hidden">
                    <div class="card-body p-0">
                        <div class="p-8 bg-primary">
                            <h3 class="text-white fw-bolder mb-0">Hubungi Guru</h3>
                            <p class="text-white opacity-75 mb-0 fs-7">Konsultasi materi atau tugas</p>
                        </div>
                        <div class="p-8">
                            <div class="d-flex align-items-center mb-5">
                                <div class="symbol symbol-40px symbol-circle me-3">
                                    <img src="{{ URL::to('assets/media/avatars/300-1.jpg') }}" alt="">
                                </div>
                                <div class="d-flex flex-column">
                                    <span class="text-gray-900 fw-bold fs-7">Bpk. Budi Santoso</span>
                                    <span class="text-muted fs-8">Wali Kelas</span>
                                </div>
                                <button class="btn btn-sm btn-icon btn-light-primary ms-auto"><i class="ki-outline ki-whatsapp fs-2"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
