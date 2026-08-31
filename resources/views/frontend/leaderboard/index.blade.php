@extends(auth()->user()->hasRole('Siswa') ? 'frontend.layout.app' : 'backend.layout.app')
@section('title', 'Leaderboard & Lencana')

@section('content')
<style>
    .gamified-header {
        background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
        border-radius: 16px;
        color: #ffffff;
        box-shadow: 0 10px 30px rgba(106, 17, 203, 0.2);
    }
    .top-rank-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
        border-radius: 16px;
    }
    .top-rank-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }
    .badge-locked {
        filter: grayscale(100%);
        opacity: 0.4;
        transition: all 0.3s ease;
    }
    .badge-unlocked {
        position: relative;
        animation: float 3s ease-in-out infinite;
        transition: all 0.3s ease;
    }
    .badge-unlocked:hover {
        transform: scale(1.1) rotate(5deg);
    }
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-5px); }
    }
    .crown-gold { color: #FFD700; filter: drop-shadow(0 2px 5px rgba(255, 215, 0, 0.5)); }
    .crown-silver { color: #C0C0C0; filter: drop-shadow(0 2px 5px rgba(192, 192, 192, 0.5)); }
    .crown-bronze { color: #CD7F32; filter: drop-shadow(0 2px 5px rgba(205, 127, 50, 0.5)); }
    .leaderboard-row {
        transition: all 0.2s ease;
        border-radius: 12px !important;
        margin-bottom: 8px;
    }
    .leaderboard-row:hover {
        background-color: #f8f9fa !important;
        transform: scale(1.01);
    }
    .my-custom-rank td {
        background-color: rgba(106, 17, 203, 0.05) !important;
    }
    .my-custom-rank td:first-child {
        border-top-left-radius: 12px;
        border-bottom-left-radius: 12px;
    }
    .my-custom-rank td:last-child {
        border-top-right-radius: 12px;
        border-bottom-right-radius: 12px;
    }
</style>

<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Prestasi & Papan Peringkat</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">Portal</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">Leaderboard</li>
            </ul>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        
        {{-- ======== DROPDOWN SELEKSI KELAS UNTUK GURU / SUPERADMIN ======== --}}
        @if($availableClasses->isNotEmpty())
            <div class="card shadow-sm border-0 mb-6">
                <div class="card-body py-4">
                    <form action="{{ route('portal.leaderboard') }}" method="GET" class="d-flex align-items-center justify-content-between flex-wrap gap-4">
                        <div class="d-flex align-items-center">
                            <i class="ki-outline ki-filter fs-3 text-gray-500 me-2"></i>
                            <span class="fw-bold text-gray-700">Pilih Kelas untuk Leaderboard:</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <select name="class_room_id" class="form-select form-select-sm w-200px" onchange="this.form.submit()">
                                @foreach($availableClasses as $class)
                                    <option value="{{ $class->id }}" {{ $class->id == $classId ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- ======== HIGHLIGHT CARD PROFIL GAMIFIKASI ======== --}}
        <div class="gamified-header p-8 p-lg-12 mb-10 position-relative overflow-hidden">
            <div class="position-absolute top-0 end-0 translate-middle-y opacity-10" style="margin-right: -100px; margin-top: -50px;">
                <i class="ki-outline ki-cup fs-5x text-white" style="font-size: 20rem !important;"></i>
            </div>
            <div class="row align-items-center position-relative z-index-1">
                <div class="col-lg-8">
                    @if(auth()->user()->hasRole('Siswa'))
                        <h2 class="fs-2hx fw-bold text-white mb-2">Halo, {{ auth()->user()->name }}! 🌟</h2>
                        <p class="fs-5 text-white-50 mb-6">Tingkatkan terus belajarmu, kumpulkan tugas tepat waktu, dapatkan nilai terbaik, dan raih posisi teratas di kelas!</p>
                        
                        <div class="d-flex flex-wrap gap-5">
                            <div class="bg-white bg-opacity-10 rounded-xl px-6 py-4 border border-white border-opacity-10">
                                <span class="fs-8 fw-semibold text-white-50 d-block mb-1 text-uppercase">Peringkat Kamu</span>
                                <span class="fs-2hx fw-extrabold text-white">#{{ $myRank['rank'] ?? '-' }} <span class="fs-5 fw-normal text-white-50">dari {{ count($leaderboard) }} siswa</span></span>
                            </div>
                            <div class="bg-white bg-opacity-10 rounded-xl px-6 py-4 border border-white border-opacity-10">
                                <span class="fs-8 fw-semibold text-white-50 d-block mb-1 text-uppercase">Total Poin</span>
                                <span class="fs-2hx fw-extrabold text-white">{{ $myRank['points'] ?? 0 }} <span class="fs-5 fw-normal text-white-50">PTS</span></span>
                            </div>
                            <div class="bg-white bg-opacity-10 rounded-xl px-6 py-4 border border-white border-opacity-10">
                                <span class="fs-8 fw-semibold text-white-50 d-block mb-1 text-uppercase">Lencana Diraih</span>
                                <span class="fs-2hx fw-extrabold text-white">{{ count($myBadgeIds) }} <span class="fs-5 fw-normal text-white-50">/ {{ count($allBadges) }}</span></span>
                            </div>
                        </div>
                    @else
                        <h2 class="fs-2hx fw-bold text-white mb-2">Pantau Prestasi Kelas 🏆</h2>
                        <p class="fs-5 text-white-50 mb-6">Berikut adalah peringkat keaktifan dan pencapaian lencana siswa pada kelas: <strong>{{ $selectedClass->name ?? 'Kelas' }}</strong></p>
                        
                        <div class="d-flex flex-wrap gap-5">
                            <div class="bg-white bg-opacity-10 rounded-xl px-6 py-4 border border-white border-opacity-10">
                                <span class="fs-8 fw-semibold text-white-50 d-block mb-1 text-uppercase">Siswa Terdaftar</span>
                                <span class="fs-2hx fw-extrabold text-white">{{ count($leaderboard) }} <span class="fs-5 fw-normal text-white-50">Siswa</span></span>
                            </div>
                            <div class="bg-white bg-opacity-10 rounded-xl px-6 py-4 border border-white border-opacity-10">
                                <span class="fs-8 fw-semibold text-white-50 d-block mb-1 text-uppercase">Rata-rata Poin Kelas</span>
                                <span class="fs-2hx fw-extrabold text-white">{{ count($leaderboard) > 0 ? round(collect($leaderboard)->avg('points')) : 0 }} <span class="fs-5 fw-normal text-white-50">PTS</span></span>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="col-lg-4 text-center mt-8 mt-lg-0">
                    <div class="d-inline-block position-relative">
                        <div class="symbol symbol-120px symbol-circle border border-4 border-white border-opacity-40 overflow-hidden shadow-lg">
                            <img src="{{ auth()->user()->avatar_url }}" alt="User Profile">
                        </div>
                        @if(auth()->user()->hasRole('Siswa'))
                            <span class="position-absolute bottom-0 start-50 translate-middle-x badge badge-success px-4 py-2 fw-bold fs-7 shadow-sm" style="margin-bottom: -10px;">
                                LVL {{ max(1, floor((($myRank['points'] ?? 0) / 300) + 1)) }}
                            </span>
                        @else
                            <span class="position-absolute bottom-0 start-50 translate-middle-x badge badge-primary px-4 py-2 fw-bold fs-7 shadow-sm" style="margin-bottom: -10px;">
                                {{ auth()->user()->hasRole('Superadmin') ? 'ADMIN' : 'GURU' }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-5 g-xl-10 mb-10">
            {{-- ======== TOP 3 PODIUM ======== --}}
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-900">Podium Kelas 🏆</h3>
                    </div>
                    <div class="card-body pt-2">
                        <div class="d-flex flex-column gap-6 mt-4">
                            @foreach(array_slice($leaderboard, 0, 3) as $top)
                                <div class="top-rank-card card bg-light-{{ $top['rank'] == 1 ? 'warning' : ($top['rank'] == 2 ? 'secondary' : 'primary') }} p-5 d-flex flex-row align-items-center">
                                    <div class="me-4 position-relative">
                                        <div class="symbol symbol-60px symbol-circle overflow-hidden shadow-sm">
                                            <img src="{{ $top['avatar'] }}" alt="Avatar">
                                        </div>
                                        <div class="position-absolute top-0 start-0 translate-middle" style="margin-left: 5px; margin-top: 5px;">
                                            @if($top['rank'] == 1)
                                                <i class="ki-outline ki-crown crown-gold fs-1"></i>
                                            @elseif($top['rank'] == 2)
                                                <i class="ki-outline ki-crown crown-silver fs-1"></i>
                                            @else
                                                <i class="ki-outline ki-crown crown-bronze fs-1"></i>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <h4 class="fs-6 fw-bold text-gray-900 text-truncate mb-1">{{ $top['name'] }}</h4>
                                        <span class="fs-7 text-gray-500 d-block">{{ $top['points'] }} PTS &bull; {{ $top['badge_count'] }} 🎖️</span>
                                    </div>
                                    <div class="text-end ms-2">
                                        <span class="fs-2hx fw-extrabold text-{{ $top['rank'] == 1 ? 'warning' : ($top['rank'] == 2 ? 'gray-600' : 'primary') }} opacity-50">#{{ $top['rank'] }}</span>
                                    </div>
                                </div>
                            @endforeach
                            @if(count($leaderboard) == 0)
                                <div class="text-center py-10 text-muted">
                                    Belum ada data peringkat.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ======== TABLE LEADERBOARD LENGKAP ======== --}}
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header border-0 pt-6">
                        <h3 class="card-title fw-bold text-gray-900">Peringkat Seluruh Kelas</h3>
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                        <th class="w-80px text-center">Rank</th>
                                        <th>Nama Siswa</th>
                                        <th class="text-center">On-Time</th>
                                        <th class="text-center">Total Nilai</th>
                                        <th class="text-center">Lencana</th>
                                        <th class="text-end pe-4">Poin</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @forelse($leaderboard as $row)
                                        @php
                                            $isMe = auth()->user()->student && $row['student_id'] === auth()->user()->student->id;
                                        @endphp
                                        <tr class="leaderboard-row {{ $isMe ? 'my-custom-rank' : '' }}">
                                            <td class="text-center">
                                                @if($row['rank'] == 1)
                                                    <span class="badge badge-warning fw-extrabold px-3 py-2 rounded-circle fs-6">1</span>
                                                @elseif($row['rank'] == 2)
                                                    <span class="badge badge-secondary fw-extrabold px-3 py-2 rounded-circle fs-6" style="background-color: #C0C0C0; color:#fff;">2</span>
                                                @elseif($row['rank'] == 3)
                                                    <span class="badge badge-bronze fw-extrabold px-3 py-2 rounded-circle fs-6" style="background-color: #CD7F32; color:#fff;">3</span>
                                                @else
                                                    <span class="text-gray-500 fw-bold fs-6">#{{ $row['rank'] }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="symbol symbol-40px symbol-circle me-3">
                                                        <img src="{{ $row['avatar'] }}" alt="Avatar">
                                                    </div>
                                                    <div class="d-flex flex-column">
                                                        <span class="text-gray-900 fw-bold text-hover-primary fs-6">{{ $row['name'] }}</span>
                                                        @if($isMe)
                                                            <span class="badge badge-light-primary fw-semibold fs-8 w-60px mt-1">Kamu</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center text-success fw-bold">
                                                {{ $row['on_time_count'] }} Tugas
                                            </td>
                                            <td class="text-center text-primary fw-bold">
                                                {{ $row['total_score'] }}
                                            </td>
                                            <td class="text-center text-warning fs-5">
                                                {{ $row['badge_count'] }} 🎖️
                                            </td>
                                            <td class="text-end fw-extrabold text-gray-900 pe-4">
                                                {{ $row['points'] }} PTS
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-10 text-muted">Belum ada peringkat untuk kelas ini.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ======== LENCANA DIGITAL (BADGES) ======== --}}
        @if(auth()->user()->hasRole('Siswa'))
            <div class="card shadow-sm border-0 mb-10">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title fw-bold text-gray-900">Galeri Lencana Digital Saya 🎖️</h3>
                </div>
                <div class="card-body">
                    <div class="row g-6">
                        @foreach($allBadges as $badge)
                            @php
                                $isUnlocked = in_array($badge->id, $myBadgeIds);
                            @endphp
                            <div class="col-xl-3 col-md-6">
                                <div class="card border border-2 {{ $isUnlocked ? 'border-light-'.$badge->color.' bg-light-'.$badge->color.' bg-opacity-20' : 'border-gray-200' }} p-6 text-center h-100">
                                    <div class="mb-4 d-inline-block">
                                        <div class="symbol symbol-80px symbol-circle {{ $isUnlocked ? 'badge-unlocked' : 'badge-locked' }} bg-white shadow-sm p-4 d-inline-flex align-items-center justify-content-center">
                                            <i class="ki-outline {{ $badge->icon }} fs-3x text-{{ $isUnlocked ? $badge->color : 'gray-400' }}"></i>
                                        </div>
                                    </div>
                                    <h4 class="fs-5 fw-bold mb-2 {{ $isUnlocked ? 'text-gray-900' : 'text-gray-400' }}">
                                        {{ $badge->name }}
                                    </h4>
                                    <p class="fs-7 text-gray-500 mb-4">{{ $badge->description }}</p>
                                    <div>
                                        @if($isUnlocked)
                                            <span class="badge badge-light-success fw-bold px-3 py-1 fs-8 text-uppercase">Terbuka ✓</span>
                                        @else
                                            <span class="badge badge-light fw-bold px-3 py-1 fs-8 text-uppercase text-gray-400">Terkunci 🔒</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <div class="card shadow-sm border-0 mb-10">
                <div class="card-header border-0 pt-6">
                    <h3 class="card-title fw-bold text-gray-900">Ketentuan Lencana Prestasi Digital 🎖️</h3>
                </div>
                <div class="card-body">
                    <div class="row g-6">
                        @foreach($allBadges as $badge)
                            <div class="col-xl-3 col-md-6">
                                <div class="card border border-2 border-gray-200 p-6 text-center h-100 bg-light-{{ $badge->color }} bg-opacity-10">
                                    <div class="mb-4 d-inline-block">
                                        <div class="symbol symbol-80px symbol-circle bg-white shadow-sm p-4 d-inline-flex align-items-center justify-content-center">
                                            <i class="ki-outline {{ $badge->icon }} fs-3x text-{{ $badge->color }}"></i>
                                        </div>
                                    </div>
                                    <h4 class="fs-5 fw-bold mb-2 text-gray-900">
                                        {{ $badge->name }}
                                    </h4>
                                    <p class="fs-7 text-gray-500 mb-0">{{ $badge->description }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
@endsection
