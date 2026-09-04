@extends('backend.layout.app')
@section('title', 'Monitoring Ujian')

@section('content')
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0">Monitoring Ujian</h1>
            <span class="text-muted fs-7 pt-1">
                @if(auth()->user()->hasRole('Guru'))
                    Sesi dari ujian yang Anda ampu.
                @else
                    Seluruh sesi ujian di sekolah Anda.
                @endif
            </span>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        @forelse($sessions as $s)
            @php
                $berjalan = $s->isWithinSchedule();
                $ikut = $s->attempts->count();
            @endphp
            <div class="card mb-3"><div class="card-body py-4 d-flex flex-stack flex-wrap gap-3">
                <div>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        @if($berjalan)
                            <span class="badge badge-success">berjalan</span>
                        @elseif($s->isUpcoming())
                            <span class="badge badge-light-warning">akan datang</span>
                        @else
                            <span class="badge badge-light">selesai</span>
                        @endif
                        <span class="badge badge-light-success">{{ $s->exam->teachingAssignment->subject->name ?? '-' }}</span>
                        <span class="badge badge-light-dark">{{ $s->classRoom->name ?? 'peserta pilihan' }}</span>
                        <span class="badge badge-light-primary">{{ $ikut }} sudah masuk</span>
                    </div>
                    <div class="fw-bold fs-5 text-gray-900">{{ $s->exam->title }}</div>
                    <div class="text-muted fs-7">
                        Sesi <b>{{ $s->name }}</b> ·
                        {{ \Carbon\Carbon::parse($s->starts_at)->format('d M Y H:i') }}–{{ \Carbon\Carbon::parse($s->ends_at)->format('H:i') }}
                        · {{ $s->duration_minutes }} menit
                        @unless(auth()->user()->hasRole('Guru'))
                            · guru: {{ $s->exam->teachingAssignment->teacher->user->name ?? '-' }}
                        @endunless
                    </div>
                </div>
                <a href="{{ route('exam-monitor.show', $s->id) }}" class="btn btn-sm btn-primary">
                    <i class="ki-outline ki-screen fs-5 me-1"></i>Pantau
                </a>
            </div></div>
        @empty
            <div class="card"><div class="card-body text-center py-10 text-muted">
                Belum ada sesi ujian yang bisa dipantau.
            </div></div>
        @endforelse
    </div>
</div>
@endsection
