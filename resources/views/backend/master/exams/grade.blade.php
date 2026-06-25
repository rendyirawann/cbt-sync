@extends('backend.layout.app')
@section('title', 'Periksa: ' . ($attempt->student->user->name ?? 'Siswa'))

@section('content')
@php
    $isManual = $exam->points_mode === 'manual';
    $isGraded = $attempt->status === 'graded';
    $isSuper  = auth()->user()->hasRole('Superadmin');
    // Setelah dinilai, Guru tidak bisa mengubah lagi; hanya Superadmin (untuk koreksi).
    $locked = $isGraded && !$isSuper;
@endphp
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center">
            <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0">Periksa Jawaban</h1>
            <span class="text-muted fs-7 pt-1">{{ $attempt->student->user->name ?? 'Siswa' }} • {{ $exam->title }}</span>
        </div>
        <a href="{{ route('exam-sessions.attempts', $attempt->exam_session_id) }}" class="btn btn-sm btn-light"><i class="ki-outline ki-arrow-left fs-4"></i> Kembali</a>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        @if($isGraded)
            @if($locked)
            <div class="alert bg-light-success border border-success border-dashed d-flex align-items-center mb-6 p-4">
                <i class="ki-outline ki-lock-2 fs-2x text-success me-3"><span class="path1"></span><span class="path2"></span></i>
                <div>Nilai sudah <b>final</b> (Nilai Akhir: <b>{{ rtrim(rtrim((string)$attempt->final_score,'0'),'.') }}</b>). Untuk perbaikan, hanya <b>Superadmin</b> yang dapat mengubah.</div>
            </div>
            @else
            <div class="alert bg-light-warning border border-warning border-dashed d-flex align-items-center mb-6 p-4">
                <i class="ki-outline ki-pencil fs-2x text-warning me-3"><span class="path1"></span><span class="path2"></span></i>
                <div><b>Mode Edit (Superadmin)</b> — nilai sudah dinilai (Nilai Akhir: <b>{{ rtrim(rtrim((string)$attempt->final_score,'0'),'.') }}</b>). Anda dapat memperbaikinya bila ada kesalahan koreksi.</div>
            </div>
            @endif
        @endif
        {{-- ringkasan PG --}}
        <div class="row g-4 mb-6">
            <div class="col-6 col-md-3"><div class="card"><div class="card-body text-center py-4"><div class="fs-2 fw-bold text-success">{{ $attempt->correct_count }}</div><div class="text-muted fs-7">Benar</div></div></div></div>
            <div class="col-6 col-md-3"><div class="card"><div class="card-body text-center py-4"><div class="fs-2 fw-bold text-danger">{{ $attempt->wrong_count }}</div><div class="text-muted fs-7">Salah</div></div></div></div>
            <div class="col-6 col-md-3"><div class="card"><div class="card-body text-center py-4"><div class="fs-2 fw-bold text-gray-500">{{ $attempt->blank_count }}</div><div class="text-muted fs-7">Kosong</div></div></div></div>
            <div class="col-6 col-md-3"><div class="card"><div class="card-body text-center py-4"><div class="fs-2 fw-bold text-primary">{{ rtrim(rtrim((string)$attempt->mc_score,'0'),'.') }}</div><div class="text-muted fs-7">Nilai PG</div></div></div></div>
        </div>

        <form action="{{ route('exam-attempts.grade.store', $attempt->id) }}" method="POST" class="custom-ajax">
            @csrf
            @foreach($exam->questions as $i => $q)
            @php $ans = $answers->get($q->id); @endphp
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="badge badge-light-{{ $q->type === 'mc' ? 'primary' : 'info' }}">Soal {{ $i+1 }} • {{ $q->type === 'mc' ? 'Pilihan Ganda' : 'Essay' }} • maks {{ rtrim(rtrim((string)$weights[$q->id],'0'),'.') }}</span>
                        @if($q->type === 'mc')
                            <span class="badge badge-{{ ($ans && $ans->is_correct) ? 'success' : 'danger' }}">{{ $ans ? (($ans->is_correct ? '+' : '') . rtrim(rtrim((string)$ans->earned_score,'0'),'.')) : '0 (kosong)' }}</span>
                        @endif
                    </div>
                    <div class="fw-semibold text-gray-900 mb-3">{!! nl2br(e($q->question_text)) !!}</div>
                    @if($q->image_path)<img src="{{ Storage::url($q->image_path) }}" class="rounded mb-3 mh-150px">@endif

                    @if($q->type === 'mc')
                        @foreach($q->options as $opt)
                        <div class="d-flex align-items-center mb-1
                            @if($opt->is_correct) text-success fw-bold
                            @elseif($ans && $ans->selected_option_id === $opt->id) text-danger fw-bold @endif">
                            <span class="badge badge-{{ $opt->is_correct ? 'success' : (($ans && $ans->selected_option_id === $opt->id) ? 'danger' : 'secondary') }} me-2">{{ $opt->label }}</span>
                            {{ $opt->option_text }}
                            @if($ans && $ans->selected_option_id === $opt->id)<span class="badge badge-light-dark ms-2">Jawaban siswa</span>@endif
                            @if($opt->is_correct)<i class="ki-outline ki-check-circle fs-5 text-success ms-2"></i>@endif
                        </div>
                        @endforeach
                    @else
                        <div class="bg-light rounded p-4 mb-3">
                            <div class="text-muted fs-8 text-uppercase fw-bold mb-1">Jawaban Siswa</div>
                            <div class="text-gray-800">{!! $ans && $ans->answer_text ? nl2br(e($ans->answer_text)) : '<span class="text-muted">(tidak dijawab)</span>' !!}</div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label required">Nilai (0–{{ rtrim(rtrim((string)$weights[$q->id],'0'),'.') }})</label>
                                <input type="number" step="0.01" min="0" max="{{ $weights[$q->id] }}" name="scores[{{ $q->id }}]" class="form-control" value="{{ $ans && $ans->earned_score !== null ? rtrim(rtrim((string)$ans->earned_score,'0'),'.') : '' }}" @disabled($locked)>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Catatan / Feedback</label>
                                <input type="text" name="feedback[{{ $q->id }}]" class="form-control" value="{{ $ans->feedback ?? '' }}" @disabled($locked)>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @endforeach

            <div class="card mb-6">
                <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                    @if($isManual)
                    <div>
                        <label class="form-label required mb-1">Nilai Akhir (mode manual)</label>
                        <input type="number" step="0.01" min="0" name="final_score" class="form-control w-200px" value="{{ $attempt->final_score !== null ? rtrim(rtrim((string)$attempt->final_score,'0'),'.') : '' }}" required @disabled($locked)>
                    </div>
                    @else
                    <div class="text-muted fs-7">Nilai akhir dihitung otomatis: <b>PG ({{ rtrim(rtrim((string)$attempt->mc_score,'0'),'.') }}) + Essay</b>{{ $exam->normalize ? ', dinormalisasi ke 0–100' : '' }}.</div>
                    @endif
                    @if($locked)
                        <span class="btn btn-light disabled"><i class="ki-outline ki-lock-2 fs-4"></i> Nilai Sudah Final</span>
                    @else
                        <button type="submit" class="btn btn-primary"><i class="ki-outline ki-check fs-4"></i> {{ $isGraded ? 'Perbarui Nilai' : 'Simpan Nilai' }}</button>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Form .custom-ajax tidak diintersep handler global modal; submit normal (full page) → redirect + flash.
</script>
@endpush
@endsection
