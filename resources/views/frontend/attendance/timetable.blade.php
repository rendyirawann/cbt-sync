@extends('frontend.layout.app')
@section('title', 'Jadwal Pelajaran Mingguan')
@section('content')

<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Jadwal Pelajaran Mingguan</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">Portal</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">Jadwal Pelajaran</li>
            </ul>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        @php
            $days = [
                1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu',
                4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'
            ];
            $todayDow = \Carbon\Carbon::now()->dayOfWeekIso;
            $nowTime  = \Carbon\Carbon::now();
        @endphp

        <div class="row g-6">
            @foreach($days as $key => $dayName)
            @php
                $isToday = ($key == $todayDow);
                $daySchedules = $schedules[$key] ?? collect();
            @endphp
            <div class="col-xl-4 col-md-6">
                <div class="card card-flush h-100 border-top border-4 {{ $isToday ? 'border-primary shadow' : 'border-gray-200' }}">
                    <div class="card-header pt-5">
                        <h3 class="card-title align-items-start flex-column">
                            <span class="card-label fw-bold text-dark fs-3">
                                {{ $dayName }}
                                @if($isToday)
                                    <span class="badge badge-light-primary ms-2 fs-8">Hari Ini</span>
                                @endif
                            </span>
                            <span class="text-muted mt-1 fw-semibold fs-7">
                                {{ $daySchedules->count() > 0 ? $daySchedules->count() . ' Mata Pelajaran' : 'Tidak ada jadwal' }}
                            </span>
                        </h3>
                    </div>
                    <div class="card-body pt-5">
                        @forelse($daySchedules as $s)
                        @php
                            $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $s->start_time);
                            $endTime   = \Carbon\Carbon::createFromFormat('H:i:s', $s->end_time);
                            $isOngoing = $isToday && $nowTime->between($startTime, $endTime);
                        @endphp
                        <div class="d-flex align-items-start mb-7 {{ $isOngoing ? 'bg-light-primary p-3 rounded' : '' }}">
                            <div class="symbol symbol-50px me-5">
                                <span class="symbol-label {{ $isOngoing ? 'bg-primary' : 'bg-light-primary' }}">
                                    <i class="ki-outline ki-book-open fs-2x {{ $isOngoing ? 'text-white' : 'text-primary' }}"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <span class="text-dark fw-bold fs-6 d-block">{{ $s->teachingAssignment->subject->name }}</span>
                                <span class="text-muted fw-semibold fs-7">{{ date('H:i', strtotime($s->start_time)) }} – {{ date('H:i', strtotime($s->end_time)) }}</span>
                                <span class="text-gray-400 fs-8 d-block">{{ $s->teachingAssignment->teacher->user->name }}</span>

                                @if($isOngoing && $s->meeting_url)
                                <div class="mt-2">
                                    <a href="{{ $s->meeting_url }}" target="_blank" class="btn btn-primary btn-sm fw-bold">
                                        <i class="ki-outline ki-video fs-4 me-1"></i> Masuk Kelas Virtual
                                    </a>
                                </div>
                                @elseif($s->meeting_url)
                                <div class="mt-2">
                                    <span class="badge badge-light-info fw-bold">
                                        <i class="ki-outline ki-video fs-7 me-1"></i> Link Virtual Tersedia
                                    </span>
                                </div>
                                @endif

                                @if($isOngoing)
                                <div class="mt-1">
                                    <span class="badge badge-success">● Sedang Berlangsung</span>
                                </div>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="d-flex flex-column flex-center py-10">
                            <i class="ki-outline ki-calendar-remove fs-3x text-gray-200 mb-3"></i>
                            <span class="text-gray-400 fw-bold">Hari Libur / Kosong</span>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
