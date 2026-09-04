@extends('backend.layout.app')
@section('title', 'Periksa: ' . ($attempt->student->user->name ?? 'Siswa'))

@section('content')
@php
    $isGraded = $attempt->status === 'graded';
    $isSuper  = auth()->user()->hasRole('Superadmin');
    // Setelah dinilai, Guru tidak bisa mengubah lagi; hanya Superadmin (untuk koreksi).
    $locked = $isGraded && !$isSuper;
@endphp

@include('partials.katex')
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center">
            <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0">Periksa Jawaban</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">Akademik</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item"><a href="{{ route('exams.index') }}" class="text-primary text-hover-dark">Ujian / CBT</a></li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item"><a href="{{ route('exams.show', $exam->id) }}" class="text-primary text-hover-dark">{{ \Illuminate\Support\Str::limit($exam->title, 26) }}</a></li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item"><a href="{{ route('exam-sessions.attempts', $attempt->exam_session_id) }}" class="text-primary text-hover-dark">Peserta &amp; Nilai</a></li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-gray-700">{{ \Illuminate\Support\Str::limit($attempt->student->user->name ?? 'Siswa', 22) }}</li>
            </ul>
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
        {{-- Peringatan anti-contek: rekap berapa kali siswa meninggalkan layar ujian --}}
        @php
            $nLock = (int) ($attempt->lock_count ?? 0);
            $nLeave = (int) ($attempt->leave_count ?? 0);
        @endphp
        @if($nLock + $nLeave > 0)
        <div class="alert bg-light-{{ $nLock > 0 ? 'danger' : 'warning' }} border border-{{ $nLock > 0 ? 'danger' : 'warning' }} border-dashed d-flex align-items-center mb-6 p-4">
            <i class="ki-outline ki-shield-cross fs-2x text-{{ $nLock > 0 ? 'danger' : 'warning' }} me-3"><span class="path1"></span><span class="path2"></span></i>
            <div>
                <b>Catatan pengawasan:</b> siswa meninggalkan layar ujian
                @if($nLock > 0)<b>{{ $nLock }}×</b> sampai <b>sesi terkunci</b> (perlu PIN guru)@endif
                @if($nLock > 0 && $nLeave > 0), dan @endif
                @if($nLeave > 0)<b>{{ $nLeave }}×</b> keluar sekejap lalu kembali (ditoleransi)@endif.
                @if($nLock + $nLeave >= 3)<span class="text-danger fw-bold">Frekuensinya tinggi — mohon diperiksa.</span>@endif
            </div>
        </div>
        @endif
        {{-- ringkasan PG --}}
        <div class="row g-4 mb-6">
            <div class="col-6 col-md-3"><div class="card"><div class="card-body text-center py-4"><div class="fs-2 fw-bold text-success">{{ $attempt->correct_count }}</div><div class="text-muted fs-7">Benar</div></div></div></div>
            <div class="col-6 col-md-3"><div class="card"><div class="card-body text-center py-4"><div class="fs-2 fw-bold text-danger">{{ $attempt->wrong_count }}</div><div class="text-muted fs-7">Salah</div></div></div></div>
            <div class="col-6 col-md-3"><div class="card"><div class="card-body text-center py-4"><div class="fs-2 fw-bold text-gray-500">{{ $attempt->blank_count }}</div><div class="text-muted fs-7">Kosong</div></div></div></div>
            <div class="col-6 col-md-3"><div class="card"><div class="card-body text-center py-4"><div class="fs-2 fw-bold text-primary">{{ rtrim(rtrim((string)$attempt->mc_score,'0'),'.') }}</div><div class="text-muted fs-7">Nilai PG</div></div></div></div>
        </div>

        <form action="{{ route('exam-attempts.grade.store', $attempt->id) }}" method="POST" class="custom-ajax" id="gradeForm">
            @csrf
            @foreach($questions as $i => $q)
            @php $ans = $answers->get($q->id); @endphp
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="badge badge-light-{{ $q->type === 'mc' ? 'primary' : 'info' }}">Soal {{ $i+1 }} • {{ $q->type === 'mc' ? 'Pilihan Ganda' : 'Essay' }} • maks {{ rtrim(rtrim(number_format((float)$weights[$q->id],2,'.',''),'0'),'.') }}</span>
                        @if($q->type === 'mc')
                            <span class="badge badge-{{ ($ans && $ans->is_correct) ? 'success' : 'danger' }}">{{ $ans ? (($ans->is_correct ? '+' : '') . rtrim(rtrim((string)$ans->earned_score,'0'),'.')) : '0 (kosong)' }}</span>
                        @endif
                    </div>
                    <div class="fw-semibold text-gray-900 mb-3">{!! nl2br(e($q->question_text)) !!}</div>
                    @if($q->image_path)<img src="{{ asset('storage/'.$q->image_path) }}" class="zoomable rounded mb-3 mh-150px">@endif

                    @if($q->type === 'mc')
                        @foreach($q->options as $opt)
                        <div class="d-flex align-items-start mb-1
                            @if($opt->is_correct) text-success fw-bold
                            @elseif($ans && $ans->selected_option_id === $opt->id) text-danger fw-bold @endif">
                            <span class="badge badge-{{ $opt->is_correct ? 'success' : (($ans && $ans->selected_option_id === $opt->id) ? 'danger' : 'secondary') }} me-2">{{ $opt->label }}</span>
                            <div>
                                @if($opt->option_text){{ $opt->option_text }}@endif
                                @if($opt->image_path)<img src="{{ asset('storage/'.$opt->image_path) }}" class="zoomable rounded d-block mt-1 mh-80px" alt="Gambar opsi {{ $opt->label }}">@endif
                            </div>
                            @if($ans && $ans->selected_option_id === $opt->id)<span class="badge badge-light-dark ms-2">Jawaban siswa</span>@endif
                            @if($opt->is_correct)<i class="ki-outline ki-check-circle fs-5 text-success ms-2"></i>@endif
                        </div>
                        @endforeach
                    @else
                        <div class="bg-light rounded p-4 mb-3">
                            <div class="text-muted fs-8 text-uppercase fw-bold mb-1">Jawaban Siswa</div>
                            <div class="text-gray-800">{!! $ans && $ans->answer_text ? nl2br(e($ans->answer_text)) : '<span class="text-muted">(tidak dijawab)</span>' !!}</div>
                        </div>
                        @if($ans && !empty($ans->answer_images))
                        <div class="mb-3">
                            <div class="text-muted fs-8 text-uppercase fw-bold mb-2">Foto Jawaban</div>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach($ans->answer_images as $img)
                                @php
                                    // Pratinjau memakai thumbnail (ringan); klik membuka versi penuh
                                    // pada lightbox berkontrol zoom.
                                    $thumb = preg_replace('/\.[^.]+$/', '', $img).'_thumb.jpg';
                                    $thumbUrl = \Illuminate\Support\Facades\Storage::disk('public')->exists($thumb) ? asset('storage/'.$thumb) : asset('storage/'.$img);
                                @endphp
                                <a href="{{ asset('storage/'.$img) }}" title="Klik untuk memperbesar">
                                    <img class="zoomable" src="{{ $thumbUrl }}" data-full="{{ asset('storage/'.$img) }}" loading="lazy" alt="Foto jawaban" style="width:130px;height:130px;object-fit:cover;border-radius:10px;border:1px solid #e4e6ef;cursor:zoom-in">
                                </a>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        @php
                            $wq = (float) $weights[$q->id];
                            $wqTxt = rtrim(rtrim(number_format($wq, 2, '.', ''), '0'), '.');
                            $sudahDinilai = $ans && $ans->graded;
                            $nilaiKini = $sudahDinilai ? (float) $ans->earned_score : null;
                            // Mode manual: angka yang diisi guru = BOBOT soal essay ini (total semua essay wajib 100).
                            // Prefill dari bobot yang pernah disimpan pada soal; bila belum pernah, bagi rata.
                            $nEssay = $questions->where('type', 'essay')->count();
                            $allocDefault = $nEssay > 0 ? round(100 / $nEssay, 2) : 0;
                            $alloc = ((float) $q->points > 1) ? (float) $q->points : $allocDefault;
                            $allocTxt = rtrim(rtrim(number_format($alloc, 2, '.', ''), '0'), '.');
                        @endphp
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                @if($exam->points_mode === 'auto')
                                    {{-- Mode Otomatis: guru cukup menandai Benar/Salah.
                                         Benar = poin penuh soal ini ({{ $wqTxt }}), Salah = 0. --}}
                                    <label class="form-label required">Jawaban Essay</label>
                                    <div class="d-flex flex-wrap gap-4 pt-1">
                                        <label class="form-check form-check-custom form-check-solid mb-0">
                                            <input class="form-check-input essay-flag" type="radio" name="essay_correct[{{ $q->id }}]" value="1"
                                                   data-weight="{{ $wq }}" @checked($sudahDinilai && $nilaiKini > 0) @disabled($locked)>
                                            <span class="form-check-label fw-bold text-success">Benar (+{{ $wqTxt }})</span>
                                        </label>
                                        <label class="form-check form-check-custom form-check-solid mb-0">
                                            <input class="form-check-input essay-flag" type="radio" name="essay_correct[{{ $q->id }}]" value="0"
                                                   data-weight="{{ $wq }}" @checked($sudahDinilai && $nilaiKini <= 0) @disabled($locked)>
                                            <span class="form-check-label fw-bold text-danger">Salah (0)</span>
                                        </label>
                                    </div>
                                @else
                                    {{-- Mode Manual: guru menentukan BOBOT soal essay ini (total seluruh essay wajib
                                         tepat 100), lalu menandai Benar/Salah. Benar = dapat bobot penuh, Salah = 0. --}}
                                    <label class="form-label required">Bobot soal ini</label>
                                    <div class="input-group mb-2">
                                        <input type="number" step="0.01" min="0.01" max="100" name="alloc[{{ $q->id }}]" class="form-control essay-alloc" value="{{ $allocTxt }}" @disabled($locked)>
                                        <span class="input-group-text">poin</span>
                                    </div>
                                    <label class="form-label required">Jawaban Essay</label>
                                    <div class="d-flex flex-wrap gap-4 pt-1">
                                        <label class="form-check form-check-custom form-check-solid mb-0">
                                            <input class="form-check-input essay-flag" type="radio" name="essay_correct[{{ $q->id }}]" value="1"
                                                   data-qid="{{ $q->id }}" @checked($sudahDinilai && $nilaiKini > 0) @disabled($locked)>
                                            <span class="form-check-label fw-bold text-success">Benar (dapat bobot penuh)</span>
                                        </label>
                                        <label class="form-check form-check-custom form-check-solid mb-0">
                                            <input class="form-check-input essay-flag" type="radio" name="essay_correct[{{ $q->id }}]" value="0"
                                                   data-qid="{{ $q->id }}" @checked($sudahDinilai && $nilaiKini <= 0) @disabled($locked)>
                                            <span class="form-check-label fw-bold text-danger">Salah (0)</span>
                                        </label>
                                    </div>
                                    <div class="form-text">Total bobot seluruh soal essay <b>wajib tepat 100</b>.</div>
                                @endif
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
                    @php
                        $numf = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
                        $sw = $exam->sectionWeights();
                        $hasMcQ = $exam->mcMaxPoints() > 0;
                        $hasEsQ = $exam->essayMaxPoints() > 0;
                    @endphp
                    <div>
                        {{-- Pratinjau langsung nilai akhir; dihitung ulang saat guru mengetik nilai essay.
                             Rumus sama untuk kedua mode: nilai PG & Essay masing-masing 0–100, lalu dirata-ratakan. --}}
                        <div class="mb-1 fs-7">
                            <span class="text-muted">Perhitungan otomatis:</span><br>
                            @if($hasMcQ)
                            <b>PG <span id="calcMc">{{ $numf($attempt->mc_score) }}</span>/{{ $numf($exam->mcMaxPoints()) }} → <span id="calcMcPct">0</span></b>
                            <span class="text-muted">× {{ round($sw['mc']) }}%</span>
                            @endif
                            @if($hasMcQ && $hasEsQ) &nbsp;+&nbsp; @endif
                            @if($hasEsQ)
                            <b>Essay <span id="calcEssay">0</span>/{{ $numf($exam->essayMaxPoints()) }} → <span id="calcEsPct">0</span></b>
                            <span class="text-muted">× {{ round($sw['essay']) }}%</span>
                            @endif
                            → <b class="text-primary">Nilai Akhir <span id="calcFinal">0</span></b>
                        </div>
                        @if($hasEsQ && $exam->points_mode !== 'auto')
                        <div class="fs-7 mb-1">Total bobot essay: <b id="essayTotal" class="text-primary">0</b> / 100
                            <span id="essayTotalWarn" class="text-danger fw-bold ms-2" style="display:none">harus tepat 100</span>
                        </div>
                        @endif
                        <div class="text-muted fs-8">
                            Mode <b>{{ $exam->points_mode === 'auto' ? 'Otomatis' : 'Manual' }}</b> —
                            {{ $exam->points_mode === 'auto' ? 'guru cukup menandai Benar/Salah pada essay' : 'guru menentukan nilai tiap essay, total maksimal 100' }}, dan nilai akhir dihitung sistem.
                        </div>
                    </div>
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

{{-- Preloader saat menyimpan nilai: form ini submit biasa (muat ulang halaman), jadi tanpa
     indikator guru tidak tahu prosesnya berjalan dan berisiko menekan tombol dua kali. --}}
<div id="saveOverlay">
    <div class="so-box">
        <span class="spinner-border text-primary" role="status" aria-hidden="true"></span>
        <div class="so-text">Menyimpan nilai...</div>
        <div class="so-sub">Mohon tunggu, jangan tutup halaman ini.</div>
    </div>
</div>
<style>
    #saveOverlay{position:fixed;inset:0;z-index:2000;background:rgba(15,23,42,.55);backdrop-filter:blur(2px);display:none;align-items:center;justify-content:center}
    #saveOverlay.show{display:flex}
    #saveOverlay .so-box{background:#fff;border-radius:16px;padding:28px 36px;text-align:center;box-shadow:0 24px 60px rgba(0,0,0,.28);min-width:260px}
    #saveOverlay .so-text{font-weight:700;color:#181C32;margin-top:14px;font-size:15px}
    #saveOverlay .so-sub{color:#7E8299;font-size:12.5px;margin-top:4px}
</style>

@push('scripts')
<script>
    // Form .custom-ajax tidak diintersep handler global modal; submit normal (full page) → redirect + flash.
    // ---- Preloader simpan nilai + cegah submit ganda ----
    (function(){
        var gform = document.getElementById('gradeForm');
        var ov = document.getElementById('saveOverlay');
        if (!gform) return;
        gform.addEventListener('submit', function(e){
            if (gform.dataset.busy === '1'){ e.preventDefault(); return; }   // klik kedua diabaikan
            gform.dataset.busy = '1';
            var btn = gform.querySelector('button[type="submit"]');
            if (btn){
                btn.classList.add('disabled');
                btn.innerHTML = '<span class="spinner-border spinner-border-sm align-middle me-2"></span> Menyimpan...';
            }
            if (ov) ov.classList.add('show');
        });
        // Kembali lewat tombol Back (halaman dari cache) → pastikan overlay tidak menempel.
        window.addEventListener('pageshow', function(){
            gform.dataset.busy = '';
            if (ov) ov.classList.remove('show');
        });
    })();

    // Pratinjau langsung nilai akhir saat guru mengetik nilai essay — mengikuti mode
    // penilaian ujian. Nilai tiap soal DIBATASI ke skor maksimalnya (sama seperti di
    // server), supaya angka pratinjau tidak pernah melebihi 100.
    (function(){
        var MC = {{ (float) $attempt->mc_score }};
        var MAXP = {{ (float) $exam->maxPoints() }};
        var NORMALIZE = {{ $exam->normalize ? 'true' : 'false' }};
        var IS_SECTION = true;   // kedua mode memakai rumus per bagian
        var MC_MAX = {{ (float) $exam->mcMaxPoints() }};
        var ES_MAX = {{ (float) $exam->essayMaxPoints() }};
        var W_MC = {{ (float) $sw['mc'] }};
        var W_ES = {{ (float) $sw['essay'] }};
        var inputs = document.querySelectorAll('input[name^="scores["]');
        var flags  = document.querySelectorAll('input.essay-flag');   // mode auto: Benar/Salah
        var fmt = function(n){ return (Math.round(n * 100) / 100).toString(); };
        var set = function(id, val, suffix){ var el = document.getElementById(id); if (el) el.textContent = fmt(val) + (suffix || ''); };
        function recalc(){
            var essay = 0;
            inputs.forEach(function(i){
                var v = parseFloat(i.value);
                if (isNaN(v)) return;
                var max = parseFloat(i.getAttribute('max'));
                if (!isNaN(max) && v > max) v = max;      // clamp seperti server
                if (v < 0) v = 0;
                essay += v;
            });
            var totalBobot = 0;
            flags.forEach(function(r){
                // Bobot soal: mode auto dari data-weight (tetap), mode manual dari input bobot.
                var w;
                if (r.dataset.weight !== undefined) {
                    w = parseFloat(r.dataset.weight) || 0;
                } else {
                    var inp = document.querySelector('input.essay-alloc[name="alloc[' + r.dataset.qid + ']"]');
                    w = inp ? (parseFloat(inp.value) || 0) : 0;
                }
                if (r.value === '1'){                       // hitung bobot sekali per soal
                    totalBobot += w;
                    if (r.checked) essay += w;              // Benar → dapat bobot penuh
                }
            });
            var totEl = document.getElementById('essayTotal');
            if (totEl){                                    // mode manual: total bobot wajib tepat 100
                totEl.textContent = fmt(totalBobot);
                var warn = document.getElementById('essayTotalWarn');
                var salah = Math.abs(totalBobot - 100) > 0.01;
                if (warn) warn.style.display = salah ? 'inline' : 'none';
                totEl.classList.toggle('text-danger', salah);
                totEl.classList.toggle('text-primary', !salah);
            }
            if (IS_SECTION){
                var mcPct = MC_MAX > 0 ? Math.max(0, MC) / MC_MAX * 100 : 0;
                var esPct = ES_MAX > 0 ? essay / ES_MAX * 100 : 0;
                set('calcEssay', essay);
                set('calcMcPct', mcPct, '%');
                set('calcEsPct', esPct, '%');
                set('calcFinal', mcPct * W_MC / 100 + esPct * W_ES / 100);
            } else {
                var total = MC + essay; if (total < 0) total = 0;
                set('calcEssay', essay);
                set('calcTotal', total);
                set('calcFinal', NORMALIZE ? (MAXP > 0 ? total / MAXP * 100 : 0) : total);
            }
        }
        inputs.forEach(function(i){ i.addEventListener('input', recalc); });
        flags.forEach(function(r){ r.addEventListener('change', recalc); });
        document.querySelectorAll('input.essay-alloc').forEach(function(i){ i.addEventListener('input', recalc); });
        recalc();
    })();
</script>
@include('partials.img-zoom', ['sel' => 'img.zoomable'])
@endpush
@endsection
