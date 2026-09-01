@extends('backend.layout.app')
@section('title', 'Dashboard')
@section('content')

<!--begin::Toolbar-->
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <!--begin::Toolbar container-->
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <!--begin::Page title-->
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <!--begin::Title-->
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Dashboard</h1>
            <!--end::Title-->
            <!--begin::Breadcrumb-->
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <!--begin::Item-->
                <li class="breadcrumb-item text-muted">
                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Home</a>
                </li>
                <!--end::Item-->
                <!--begin::Item-->
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                </li>
                <!--end::Item-->
                <!--begin::Item-->
                <li class="breadcrumb-item text-muted">Dashboards</li>
                <!--end::Item-->
            </ul>
            <!--end::Breadcrumb-->
        </div>
        <!--end::Page title-->
    </div>
    <!--end::Toolbar container-->
</div>
<!--end::Toolbar-->

<div class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        <!-- Dashboard Header -->
        <div class="d-flex flex-column flex-column-fluid mb-8">
            <h1 class="text-gray-900 fw-bold mb-1">Selamat Datang, {{ auth()->user()->name }}!</h1>
            <div class="text-muted fw-semibold fs-6">
                @if($stats['type'] == 'admin')
                    Ringkasan aktivitas seluruh sekolah hari ini.
                @elseif($stats['type'] == 'guru')
                    Ringkasan kegiatan mengajar Anda di semester ini.
                @else
                    Mari lanjut belajar! Berikut materi terbaru untuk kelas Anda.
                @endif
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
            @if($stats['type'] == 'admin')
                <!-- Admin Cards -->
                <div class="col-md-6 col-lg-3">
                    <div class="card card-flush h-md-100 mb-5 mb-xl-10 bg-danger">
                        <div class="card-header pt-5"><div class="card-title d-flex flex-column">
                            <span class="fs-2hx fw-bold text-white lh-1">{{ $stats['schools'] }}</span>
                            <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Total Sekolah</span>
                        </div></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card card-flush h-md-100 mb-5 mb-xl-10 bg-primary">
                        <div class="card-header pt-5"><div class="card-title d-flex flex-column">
                            <span class="fs-2hx fw-bold text-white lh-1">{{ $stats['teachers'] }}</span>
                            <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Tenaga Pengajar</span>
                        </div></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card card-flush h-md-100 mb-5 mb-xl-10 bg-success">
                        <div class="card-header pt-5"><div class="card-title d-flex flex-column">
                            <span class="fs-2hx fw-bold text-white lh-1">{{ $stats['students'] }}</span>
                            <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Siswa Terdaftar</span>
                        </div></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card card-flush h-md-100 mb-5 mb-xl-10 bg-info">
                        <div class="card-header pt-5"><div class="card-title d-flex flex-column">
                            <span class="fs-2hx fw-bold text-white lh-1">{{ $stats['classes'] }}</span>
                            <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Rombel Aktif</span>
                        </div></div>
                    </div>
                </div>
            @elseif($stats['type'] == 'guru')
                <!-- Guru Cards -->
                <div class="col-md-6 col-lg-3">
                    <div class="card card-flush h-md-100 mb-5 mb-xl-10 bg-primary">
                        <div class="card-header pt-5"><div class="card-title d-flex flex-column">
                            <span class="fs-2hx fw-bold text-white lh-1">{{ $stats['my_classes'] }}</span>
                            <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Kelas Diampu</span>
                        </div></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card card-flush h-md-100 mb-5 mb-xl-10 bg-success">
                        <div class="card-header pt-5"><div class="card-title d-flex flex-column">
                            <span class="fs-2hx fw-bold text-white lh-1">{{ $stats['my_subjects'] }}</span>
                            <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Mata Pelajaran</span>
                        </div></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card card-flush h-md-100 mb-5 mb-xl-10 bg-info">
                        <div class="card-header pt-5"><div class="card-title d-flex flex-column">
                            <span class="fs-2hx fw-bold text-white lh-1">{{ $stats['my_modules'] }}</span>
                            <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Modul Diunggah</span>
                        </div></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card card-flush h-md-100 mb-5 mb-xl-10 bg-warning">
                        <div class="card-header pt-5"><div class="card-title d-flex flex-column">
                            <span class="fs-2hx fw-bold text-white lh-1">{{ $stats['my_students'] }}</span>
                            <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Total Siswa</span>
                        </div></div>
                    </div>
                </div>
            @else
                <!-- Siswa Cards -->
                <div class="col-md-6 col-lg-6">
                    <div class="card card-flush h-md-100 mb-5 mb-xl-10 bg-primary">
                        <div class="card-header pt-5"><div class="card-title d-flex flex-column">
                            <span class="fs-2hx fw-bold text-white lh-1">{{ $stats['my_subjects'] ?? 0 }}</span>
                            <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Mata Pelajaran Saya</span>
                        </div></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-6">
                    <div class="card card-flush h-md-100 mb-5 mb-xl-10 bg-success">
                        <div class="card-header pt-5"><div class="card-title d-flex flex-column">
                            <span class="fs-2hx fw-bold text-white lh-1">{{ $stats['new_modules'] ?? 0 }}</span>
                            <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Modul Pembelajaran</span>
                        </div></div>
                    </div>
                </div>
            @endif
        </div>

        <div class="row g-5 g-xl-10">
            <!-- Recent Activity Table -->
            <div class="col-xl-8 mb-5 mb-xl-10">
                <div class="card card-flush h-xl-100">
                    <div class="card-header pt-7">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-800">
                                @if($stats['type'] == 'admin') Penugasan Guru Terbaru
                                @elseif($stats['type'] == 'guru') Modul Terbaru Saya
                                @else Materi Terbaru Untuk Anda
                                @endif
                            </span>
                            <span class="text-gray-500 mt-1 fw-semibold fs-6">5 data terakhir</span>
                        </h3>
                    </div>
                    <div class="card-body pt-6">
                        <div class="table-responsive">
                            <table class="table table-row-dashed align-middle gs-0 gy-3">
                                <thead>
                                    <tr class="fs-7 fw-bold text-gray-500 border-bottom-0">
                                        @if($stats['type'] == 'admin')
                                            <th class="min-w-175px">GURU</th>
                                            <th class="text-end">PELAJARAN</th>
                                            <th class="text-end">KELAS</th>
                                        @elseif($stats['type'] == 'guru')
                                            <th class="min-w-175px">JUDUL MODUL</th>
                                            <th class="text-end">PELAJARAN</th>
                                            <th class="text-end">KELAS</th>
                                        @else
                                            <th class="min-w-175px">MODUL</th>
                                            <th class="text-end">GURU</th>
                                            <th class="text-end">MATA PELAJARAN</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentData as $item)
                                    <tr>
                                        @if($stats['type'] == 'admin')
                                            <td>{{ $item->teacher->user->name ?? '-' }}</td>
                                            <td class="text-end">{{ $item->subject->name ?? '-' }}</td>
                                            <td class="text-end">{{ $item->classRoom->name ?? '-' }}</td>
                                        @elseif($stats['type'] == 'guru')
                                            <td>{{ $item->title }}</td>
                                            <td class="text-end">{{ $item->teachingAssignment->subject->name ?? '-' }}</td>
                                            <td class="text-end">{{ $item->teachingAssignment->classRoom->name ?? '-' }}</td>
                                        @else
                                            <td>{{ $item->title }}</td>
                                            <td class="text-end">{{ $item->teachingAssignment->teacher->user->name ?? '-' }}</td>
                                            <td class="text-end">{{ $item->teachingAssignment->subject->name ?? '-' }}</td>
                                        @endif
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-xl-4 mb-5 mb-xl-10">
                <div class="card card-flush h-xl-100">
                    <div class="card-header pt-7"><h3 class="card-title fw-bold text-gray-800">Aksi Cepat</h3></div>
                    <div class="card-body pt-5">
                        <div class="d-flex flex-column gap-3">
                            @if($stats['type'] == 'admin')
                                <a href="{{ route('teachers.index') }}" class="btn btn-light-primary p-5 mb-2 text-start d-flex align-items-center">
                                    <i class="ki-outline ki-user fs-2x me-5"></i><span>Kelola Guru</span>
                                </a>
                                <a href="{{ route('students.index') }}" class="btn btn-light-success p-5 mb-2 text-start d-flex align-items-center">
                                    <i class="ki-outline ki-profile-user fs-2x me-5"></i><span>Kelola Siswa</span>
                                </a>
                            @elseif($stats['type'] == 'guru')
                                <a href="{{ route('learning-modules.index') }}" class="btn btn-light-primary p-5 mb-2 text-start d-flex align-items-center">
                                    <i class="ki-outline ki-book fs-2x me-5"></i><span>Unggah Modul Baru</span>
                                </a>
                            @else
                                <a href="{{ route('learning-modules.index') }}" class="btn btn-light-success p-5 mb-2 text-start d-flex align-items-center">
                                    <i class="ki-outline ki-search-list fs-2x me-5"></i><span>Cari Materi Belajar</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
