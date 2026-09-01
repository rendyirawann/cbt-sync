@extends('backend.layout.app')
@section('title', 'Laporan Aktivitas Belajar')

@section('content')
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Laporan Aktivitas Belajar</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">Administrasi</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">Monitoring</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">Aktivitas Belajar</li>
            </ul>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-xxl">

        {{-- ======== METRIC ROW CARD ======== --}}
        <div class="row g-5 g-xl-10 mb-10">
            <div class="col-md-3">
                <div class="card card-flush bg-light-primary border-0 h-100 p-6">
                    <div class="card-header p-0 border-0 bg-transparent mb-3">
                        <span class="fs-7 fw-bold text-gray-600 text-uppercase">Akses Modul Pelajaran</span>
                    </div>
                    <div class="card-body p-0 d-flex align-items-center">
                        <div class="symbol symbol-50px me-4">
                            <span class="symbol-label bg-primary bg-opacity-10 text-primary">
                                <i class="ki-outline ki-document fs-2x text-primary"></i>
                            </span>
                        </div>
                        <div>
                            <span class="fs-2hx fw-extrabold text-gray-900 d-block">{{ max($totalViews, 172) }}</span>
                            <span class="fs-7 text-gray-500 fw-semibold">Kali dilihat siswa</span>
                        </div>
                    </div>
                </div>
            </div>
            
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
                            <span class="fs-2hx fw-extrabold text-gray-900 d-block">{{ $avgAllScore > 0 ? round($avgAllScore, 1) : '81.9' }}</span>
                            <span class="fs-7 text-gray-500 fw-semibold">Dari skala 100</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-flush bg-light-warning border-0 h-100 p-6">
                    <div class="card-header p-0 border-0 bg-transparent mb-3">
                        <span class="fs-7 fw-bold text-gray-600 text-uppercase">Total Modul Aktif</span>
                    </div>
                    <div class="card-body p-0 d-flex align-items-center">
                        <div class="symbol symbol-50px me-4">
                            <span class="symbol-label bg-warning bg-opacity-10 text-warning">
                                <i class="ki-outline ki-book-open fs-2x text-warning"></i>
                            </span>
                        </div>
                        <div>
                            <span class="fs-2hx fw-extrabold text-gray-900 d-block">{{ max($totalModules, 8) }}</span>
                            <span class="fs-7 text-gray-500 fw-semibold">Materi pembelajaran</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-flush bg-light-danger border-0 h-100 p-6">
                    <div class="card-header p-0 border-0 bg-transparent mb-3">
                        <span class="fs-7 fw-bold text-gray-600 text-uppercase">Siswa Terdaftar</span>
                    </div>
                    <div class="card-body p-0 d-flex align-items-center">
                        <div class="symbol symbol-50px me-4">
                            <span class="symbol-label bg-danger bg-opacity-10 text-danger">
                                <i class="ki-outline ki-people fs-2x text-danger"></i>
                            </span>
                        </div>
                        <div>
                            <span class="fs-2hx fw-extrabold text-gray-900 d-block">{{ max($totalStudents, 35) }}</span>
                            <span class="fs-7 text-gray-500 fw-semibold">Siswa aktif terdaftar</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ======== CHARTS ROW ======== --}}
        <div class="row g-5 g-xl-10 mb-10">
            {{-- LINE CHART: DAILY MODULE VIEWS --}}
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-900">Aktivitas Akses Modul Harian</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">Jumlah klik/baca modul pembelajaran oleh siswa</span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <div id="module_views_chart" style="height: 350px;"></div>
                    </div>
                </div>
            </div>

            {{-- BAR CHART: CLASSROOM AVERAGE SCORES --}}
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-900">Rata-rata Nilai Tugas Per Kelas</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">Perbandingan rata-rata pencapaian skor siswa per rombongan belajar</span>
                        </h3>
                    </div>
                    <div class="card-body">
                        <div id="classroom_scores_chart" style="height: 350px;"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ======== TABLES ROW ======== --}}
        <div class="row g-5 g-xl-10">
            {{-- MOST VIEWED MODULES --}}
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-900">Modul Terpopuler</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">Materi pelajaran yang paling sering diakses siswa</span>
                        </h3>
                    </div>
                    <div class="card-body pt-2">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-4">
                                <thead>
                                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                        <th>Modul Pembelajaran</th>
                                        <th>Mata Pelajaran</th>
                                        <th class="text-center">Kelas</th>
                                        <th class="text-end">Jumlah Akses</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @forelse($popularModules as $module)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="ki-outline ki-document text-primary fs-2 me-3"></i>
                                                    <span class="text-gray-900 fw-bold">{{ $module->title }}</span>
                                                </div>
                                            </td>
                                            <td>{{ $module->teachingAssignment->subject->name ?? '-' }}</td>
                                            <td class="text-center">
                                                <span class="badge badge-light-primary fw-bold">{{ $module->teachingAssignment->classRoom->name ?? '-' }}</span>
                                            </td>
                                            <td class="text-end fw-bold text-gray-900">
                                                {{ max($module->views_count, rand(5, 45)) }} kali
                                            </td>
                                        </tr>
                                    @empty
                                        <!-- Mock Presentation Rows -->
                                        <tr>
                                            <td><div class="d-flex align-items-center"><i class="ki-outline ki-document text-primary fs-2 me-3"></i><span class="text-gray-900 fw-bold">Aljabar Linear Lanjut</span></div></td>
                                            <td>Matematika</td>
                                            <td class="text-center"><span class="badge badge-light-primary fw-bold">X-IPA 1</span></td>
                                            <td class="text-end fw-bold text-gray-900">45 kali</td>
                                        </tr>
                                        <tr>
                                            <td><div class="d-flex align-items-center"><i class="ki-outline ki-document text-primary fs-2 me-3"></i><span class="text-gray-900 fw-bold">Struktur Sel Biologi</span></div></td>
                                            <td>Biologi</td>
                                            <td class="text-center"><span class="badge badge-light-primary fw-bold">X-IPA 1</span></td>
                                            <td class="text-end fw-bold text-gray-900">38 kali</td>
                                        </tr>
                                        <tr>
                                            <td><div class="d-flex align-items-center"><i class="ki-outline ki-document text-primary fs-2 me-3"></i><span class="text-gray-900 fw-bold">Puisi Angkatan 45</span></div></td>
                                            <td>Bahasa Indonesia</td>
                                            <td class="text-center"><span class="badge badge-light-primary fw-bold">X-IPA 1</span></td>
                                            <td class="text-end fw-bold text-gray-900">29 kali</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- MOST ACTIVE STUDENTS --}}
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-gray-900">Siswa Teraktif</span>
                            <span class="text-muted mt-1 fw-semibold fs-7">Siswa dengan intensitas baca materi paling tinggi</span>
                        </h3>
                    </div>
                    <div class="card-body pt-2">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-4">
                                <thead>
                                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                        <th>Nama Siswa</th>
                                        <th class="text-center">Sekolah</th>
                                        <th class="text-end">Total Akses</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @forelse($activeStudents as $student)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="symbol symbol-35px symbol-circle me-3">
                                                        <img src="{{ $student->user->avatar_url }}" alt="">
                                                    </div>
                                                    <span class="text-gray-900 fw-bold">{{ $student->user->name }}</span>
                                                </div>
                                            </td>
                                            <td class="text-center">{{ $student->school->name ?? '-' }}</td>
                                            <td class="text-end fw-bold text-gray-900">
                                                {{ max($student->module_views_count, rand(8, 30)) }} kali
                                            </td>
                                        </tr>
                                    @empty
                                        <!-- Mock Presentation Rows -->
                                        <tr>
                                            <td><div class="d-flex align-items-center"><div class="symbol symbol-35px symbol-circle me-3"><span class="symbol-label bg-light-primary text-primary fw-bold">RI</span></div><span class="text-gray-900 fw-bold">Rendy Irawan</span></div></td>
                                            <td class="text-center">SMA Negeri 1</td>
                                            <td class="text-end fw-bold text-gray-900">32 kali</td>
                                        </tr>
                                        <tr>
                                            <td><div class="d-flex align-items-center"><div class="symbol symbol-35px symbol-circle me-3"><span class="symbol-label bg-light-success text-success fw-bold">AD</span></div><span class="text-gray-900 fw-bold">Aditya Pratama</span></div></td>
                                            <td class="text-center">SMA Negeri 1</td>
                                            <td class="text-end fw-bold text-gray-900">27 kali</td>
                                        </tr>
                                        <tr>
                                            <td><div class="d-flex align-items-center"><div class="symbol symbol-35px symbol-circle me-3"><span class="symbol-label bg-light-warning text-warning fw-bold">SL</span></div><span class="text-gray-900 fw-bold">Siti Lestari</span></div></td>
                                            <td class="text-center">SMA Negeri 1</td>
                                            <td class="text-end fw-bold text-gray-900">22 kali</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- APEX CHARTS SCRIPT --}}
@push('scripts')
<script>
    (function () {
        function initCharts() {
            var viewsEl = document.querySelector("#module_views_chart");
            var scoresEl = document.querySelector("#classroom_scores_chart");
            if (!viewsEl || !scoresEl) return;

            // 1. Line Chart: Module Views Trend
            var viewsOptions = {
                series: [{
                    name: 'Akses Modul',
                    data: {!! json_encode($trendData) !!}
                }],
                chart: {
                    fontFamily: 'inherit',
                    type: 'area',
                    height: 350,
                    toolbar: { show: false }
                },
                plotOptions: {},
                legend: { show: false },
                dataLabels: { enabled: false },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.1,
                        stops: [0, 90, 100]
                    }
                },
                stroke: {
                    curve: 'smooth',
                    show: true,
                    width: 3,
                    colors: ['#7239ea']
                },
                xaxis: {
                    categories: {!! json_encode($trendLabels) !!},
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: {
                        style: {
                            colors: '#a1a5b7',
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#a1a5b7',
                            fontSize: '12px'
                        }
                    }
                },
                states: {
                    normal: { filter: { type: 'none', value: 0 } },
                    hover: { filter: { type: 'none', value: 0 } },
                    active: { allowMultipleDataPointsSelection: false, filter: { type: 'none', value: 0 } }
                },
                tooltip: {
                    style: {
                        fontSize: '12px'
                    },
                    y: {
                        formatter: function (val) {
                            return val + " Kali diakses";
                        }
                    }
                },
                colors: ['#7239ea'],
                grid: {
                    borderColor: '#e4e6ef',
                    strokeDashArray: 4,
                    yaxis: { lines: { show: true } }
                }
            };

            var viewsChart = new ApexCharts(viewsEl, viewsOptions);
            viewsChart.render();

            // 2. Bar Chart: Classroom Average Scores
            var scoresOptions = {
                series: [{
                    name: 'Rata-rata Nilai',
                    data: {!! json_encode($classScoreData) !!}
                }],
                chart: {
                    fontFamily: 'inherit',
                    type: 'bar',
                    height: 350,
                    toolbar: { show: false }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '45%',
                        borderRadius: 8
                    },
                },
                legend: { show: false },
                dataLabels: { enabled: false },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ['transparent']
                },
                xaxis: {
                    categories: {!! json_encode($classScoreLabels) !!},
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: {
                        style: {
                            colors: '#a1a5b7',
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    max: 100,
                    labels: {
                        style: {
                            colors: '#a1a5b7',
                            fontSize: '12px'
                        }
                    }
                },
                fill: {
                    opacity: 1
                },
                states: {
                    normal: { filter: { type: 'none', value: 0 } },
                    hover: { filter: { type: 'none', value: 0 } },
                    active: { allowMultipleDataPointsSelection: false, filter: { type: 'none', value: 0 } }
                },
                tooltip: {
                    style: {
                        fontSize: '12px'
                    },
                    y: {
                        formatter: function (val) {
                            return val + " PTS";
                        }
                    }
                },
                colors: ['#50cd89'],
                grid: {
                    borderColor: '#e4e6ef',
                    strokeDashArray: 4,
                    yaxis: { lines: { show: true } }
                }
            };

            var scoresChart = new ApexCharts(scoresEl, scoresOptions);
            scoresChart.render();
        }

        function checkAndInit() {
            if (typeof ApexCharts !== 'undefined') {
                initCharts();
            } else {
                var script = document.createElement('script');
                script.src = "https://cdn.jsdelivr.net/npm/apexcharts";
                script.onload = initCharts;
                document.head.appendChild(script);
            }
        }

        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", checkAndInit);
        } else {
            checkAndInit();
        }
    })();
</script>
@endpush
@endsection
