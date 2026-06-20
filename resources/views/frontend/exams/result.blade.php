@extends('frontend.layout.app')
@section('title', 'Hasil Ujian')

@section('content')
@php
    $exam = $attempt->session->exam;
    $showResult = $attempt->session->show_result;
    $graded = $attempt->status === 'graded';
    $passed = $graded && (float) $attempt->final_score >= (float) $exam->pass_score;
@endphp
<div class="app-content flex-column-fluid">
    <div class="app-container container-xxl py-10">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-body text-center p-10">
                        <h2 class="fw-bold text-gray-900 mb-1">{{ $exam->title }}</h2>
                        <p class="text-muted mb-8">{{ $attempt->session->name }} • {{ $exam->teachingAssignment->subject->name ?? '' }}</p>

                        @if($graded && $showResult)
                            <div class="symbol symbol-100px mx-auto mb-5">
                                <span class="symbol-label fs-1 fw-bold text-white" style="background:{{ $passed ? 'linear-gradient(135deg,#10B981,#059669)' : 'linear-gradient(135deg,#FB7185,#E11D48)' }}">
                                    {{ rtrim(rtrim((string)$attempt->final_score,'0'),'.') }}
                                </span>
                            </div>
                            <h3 class="fw-bold {{ $passed ? 'text-success' : 'text-danger' }} mb-1">{{ $passed ? 'Selamat, Lulus! 🎉' : 'Belum Mencapai KKM' }}</h3>
                            <p class="text-muted mb-8">Nilai akhir <b>{{ rtrim(rtrim((string)$attempt->final_score,'0'),'.') }}</b> (KKM {{ rtrim(rtrim((string)$exam->pass_score,'0'),'.') }})</p>

                            <div class="row g-4 mb-4">
                                <div class="col-4"><div class="bg-light-success rounded p-4"><div class="fs-2 fw-bold text-success">{{ $attempt->correct_count }}</div><div class="text-muted fs-8">BENAR</div></div></div>
                                <div class="col-4"><div class="bg-light-danger rounded p-4"><div class="fs-2 fw-bold text-danger">{{ $attempt->wrong_count }}</div><div class="text-muted fs-8">SALAH</div></div></div>
                                <div class="col-4"><div class="bg-light-secondary rounded p-4"><div class="fs-2 fw-bold text-gray-600">{{ $attempt->blank_count }}</div><div class="text-muted fs-8">KOSONG</div></div></div>
                            </div>
                            <div class="d-flex justify-content-center gap-6 text-gray-600 fs-7 mb-6">
                                <span>Nilai PG: <b>{{ rtrim(rtrim((string)$attempt->mc_score,'0'),'.') }}</b></span>
                                @if($exam->hasEssay())<span>Nilai Essay: <b>{{ rtrim(rtrim((string)$attempt->essay_score,'0'),'.') }}</b></span>@endif
                            </div>
                        @elseif(!$graded)
                            <div class="symbol symbol-100px mx-auto mb-5"><span class="symbol-label bg-light-warning"><i class="ki-outline ki-time fs-3x text-warning"></i></span></div>
                            <h3 class="fw-bold text-gray-800 mb-1">Jawaban Terkumpul</h3>
                            <p class="text-muted mb-6">Ada soal essay yang perlu diperiksa guru. Nilai akhir akan muncul setelah penilaian selesai.</p>
                            @if($exam->hasMc())
                            <div class="d-flex justify-content-center gap-6 text-gray-600 fs-7 mb-2">
                                <span class="text-success">Benar: <b>{{ $attempt->correct_count }}</b></span>
                                <span class="text-danger">Salah: <b>{{ $attempt->wrong_count }}</b></span>
                                <span>Kosong: <b>{{ $attempt->blank_count }}</b></span>
                            </div>
                            @endif
                        @else
                            <div class="symbol symbol-100px mx-auto mb-5"><span class="symbol-label bg-light-primary"><i class="ki-outline ki-check-circle fs-3x text-primary"></i></span></div>
                            <h3 class="fw-bold text-gray-800 mb-1">Ujian Selesai</h3>
                            <p class="text-muted mb-6">Nilai akan diumumkan oleh guru.</p>
                        @endif

                        <a href="{{ route('student.exams.index') }}" class="btn btn-light-primary"><i class="ki-outline ki-arrow-left fs-4"></i> Kembali ke Daftar Ujian</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
