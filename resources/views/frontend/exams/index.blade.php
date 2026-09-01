@extends('frontend.layout.app')
@section('title', 'Ujian Saya')

@section('content')
<div class="app-content flex-column-fluid">
    <div class="app-container container-xxl py-6">
        <div class="mb-6">
            <h1 class="fw-bold text-gray-900 fs-2 mb-1">Ujian Online (CBT)</h1>
            <p class="text-muted fs-6 mb-0">Daftar ujian yang dijadwalkan untuk Anda.</p>
        </div>

        <div class="row g-5">
            @forelse($sessions as $s)
            @php
                $a = $attempts->get($s->id);
                $upcoming = $s->isUpcoming();
                $within = $s->isWithinSchedule();
                $finished = $s->isFinished();
            @endphp
            <div class="col-md-6 col-xl-4">
                <div class="card card-premium h-100 shadow-sm">
                    <div class="card-body p-6 d-flex flex-column">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="badge badge-light-primary">{{ $s->exam->teachingAssignment->subject->name ?? 'Mapel' }}</span>
                            @if($a && $a->status === 'graded')<span class="badge badge-light-success">Selesai</span>
                            @elseif($a && $a->status === 'submitted')<span class="badge badge-light-warning">Menunggu nilai</span>
                            @elseif($within)<span class="badge badge-light-success">Dibuka</span>
                            @elseif($upcoming)<span class="badge badge-light-info">Terjadwal</span>
                            @else<span class="badge badge-light-secondary">Ditutup</span>@endif
                        </div>
                        <h3 class="fw-bold text-gray-900 mb-1">{{ $s->exam->title }}</h3>
                        <div class="text-muted fs-7 mb-1">{{ $s->name }}</div>
                        <div class="text-gray-600 fs-7 mb-1"><i class="ki-outline ki-calendar fs-6 me-1"></i> {{ \Carbon\Carbon::parse($s->starts_at)->format('d M Y H:i') }} – {{ \Carbon\Carbon::parse($s->ends_at)->format('H:i') }}</div>
                        <div class="text-gray-600 fs-7 mb-4"><i class="ki-outline ki-timer fs-6 me-1"></i> Durasi {{ $s->duration_minutes }} menit</div>

                        <div class="mt-auto">
                            @if($a && in_array($a->status, ['submitted','graded']))
                                <a href="{{ route('student.exams.result', $a->id) }}" class="btn btn-light-primary w-100">Lihat Hasil</a>
                            @elseif($a && $a->status === 'in_progress' && $within)
                                <a href="{{ route('student.exams.attempt', $s->id) }}" class="btn btn-warning w-100">Lanjutkan Ujian</a>
                            @elseif(!$a && $within)
                                <form action="{{ route('student.exams.start', $s->id) }}" method="POST" class="start-form">
                                    @csrf
                                    <button type="submit" class="btn btn-primary w-100"><i class="ki-outline ki-rocket fs-4"></i> Mulai Ujian</button>
                                </form>
                            @elseif($upcoming)
                                <button class="btn btn-light w-100" disabled>Belum dibuka</button>
                            @else
                                <button class="btn btn-light w-100" disabled>{{ $a ? 'Selesai' : 'Terlewat' }}</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="card"><div class="card-body text-center py-15">
                    <i class="ki-outline ki-questionnaire-tablet fs-5x text-gray-300 mb-4"></i>
                    <h3 class="text-gray-800">Belum ada ujian</h3>
                    <p class="text-muted">Ujian yang dijadwalkan guru akan muncul di sini.</p>
                </div></div>
            </div>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.start-form').forEach(f => {
        f.addEventListener('submit', function(e){
            e.preventDefault();
            Swal.fire({
                title: 'Mulai ujian sekarang?',
                text: 'Waktu akan langsung berjalan dan tidak bisa diulang.',
                icon: 'warning', showCancelButton: true,
                confirmButtonText: 'Ya, Mulai!', cancelButtonText: 'Batal', confirmButtonColor: '#4F46E5'
            }).then(r => { if (r.isConfirmed) f.submit(); });
        });
    });
</script>
@endpush
@endsection
