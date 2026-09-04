@extends('frontend.layout.app')
@section('title', 'Mengerjakan: ' . $exam->title)

@push('stylesheets')
<style>
    /* Mode ujian: sembunyikan navbar & footer agar siswa tidak salah klik menu lain */
    #kt_app_header, #kt_app_footer { display:none !important; }
    #kt_wrapper { padding-top:16px !important; }
    .qnav-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:8px}
    .qnav{height:42px;border-radius:10px;border:1.5px solid #E5E9F2;background:#fff;font-weight:700;color:#475569;cursor:pointer;transition:.15s;display:flex;align-items:center;justify-content:center}
    .qnav:hover{border-color:#4F46E5;color:#4F46E5}
    .qnav.answered{background:rgba(5,150,105,.12);border-color:#059669;color:#059669}
    .qnav.current{background:linear-gradient(135deg,#4F46E5,#7C3AED);border-color:transparent;color:#fff;box-shadow:0 6px 16px rgba(79,70,229,.35)}
    .opt-label{cursor:pointer;transition:.15s}
    .exam-panel{position:sticky;top:16px}
    /* Soal panjang / bergambar tetap bisa di-scroll layar (alur normal halaman) */
    .exam-q img{max-width:100%;height:auto;border-radius:10px}
    .exam-q{overflow-wrap:anywhere}
    .rdev-thumb img{width:88px;height:88px;object-fit:cover;border-radius:10px;border:1px solid #e4e6ef;display:block}
    .photo-del{position:absolute;top:-8px;right:-8px;width:22px;height:22px;border-radius:50%;border:none;background:#E11D48;color:#fff;font-size:15px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center}
    /* Dialog konfirmasi (SweetAlert) default z-index 1060, sedangkan gerbang layar penuh /
       kunci sesi memakai 3000–3500. Tanpa ini, dialog "Keluar & Selesai Ujian" muncul di
       BELAKANG overlay sehingga tombol terlihat seperti tidak berfungsi. */
    .swal2-container{z-index:6000 !important}
    /* ---- Perbesar gambar (soal / opsi PG / foto jawaban) ----
       z-index 2900: di atas isi halaman, tapi TETAP di bawah gerbang layar penuh (3000),
       gerbang foto (3400) & kunci sesi (3500) supaya penguncian selalu menang. */
    .exam-q img, .rdev-thumb img{cursor:zoom-in}
    /* Petak foto yang sedang diunggah: pratinjau diredupkan + spinner di atasnya */
    .rdev-thumb.foto-memuat img{filter:brightness(.55) blur(1px);cursor:default}
    .rdev-thumb.foto-memuat{position:relative}
    .foto-spinner{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;background:rgba(17,24,39,.45);border-radius:10px;color:#fff}
    .foto-spinner-teks{font-size:10px;font-weight:700;letter-spacing:.02em}
    .rdev-thumb.foto-memuat:not(:has(img)){width:88px;height:88px;background:#e4e6ef;border-radius:10px}
@media (max-width:991.98px){
        .exam-panel{position:static;margin-bottom:1.25rem}
        .qnav-grid{grid-template-columns:repeat(8,1fr)}
    }
    @media (max-width:575.98px){
        .qnav-grid{grid-template-columns:repeat(6,1fr)}
        .opt-label{padding:.75rem !important}
        .exam-q .fs-4{font-size:1.05rem !important}
    }
</style>
@endpush

@section('content')
<div class="app-content flex-column-fluid">
    <div class="app-container container-xxl py-6">
        @include('partials.katex')
        @include('partials.math-editor')   {{-- ƒx Rumus (WYSIWYG) untuk jawaban essay siswa (.math-input) --}}

        <div class="row g-5">
            {{-- ====== Panel Navigasi (kiri di desktop) ====== --}}
            <div class="col-lg-4 order-lg-2">
                <div class="card shadow-sm exam-panel">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="text-muted fs-8 text-uppercase fw-bold">Sisa Waktu</div>
                            <div class="badge badge-light-danger fs-2 fw-bold py-3 px-5 mt-1"><i class="ki-outline ki-timer fs-2 me-1"></i> <span id="timer">--:--</span></div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-gray-800">Navigasi Soal</span>
                            <span class="badge badge-light-success"><span id="answeredCount">0</span>/{{ $questions->count() }} terjawab</span>
                        </div>
                        <div class="qnav-grid mb-4">
                            @foreach($questions as $i => $q)
                            @php $a = $answers->get($q->id); $isAns = $a && ($a->selected_option_id || trim((string)($a->answer_text ?? '')) !== '' || !empty($a->answer_images)); @endphp
                            <button type="button" class="qnav {{ $isAns ? 'answered' : '' }} {{ $i===0 ? 'current' : '' }}" data-goto="{{ $i }}" data-qid="{{ $q->id }}">{{ $i+1 }}</button>
                            @endforeach
                        </div>
                        <div class="d-flex gap-3 fs-8 text-muted mb-4">
                            <span><span class="badge badge-circle badge-success w-10px h-10px"></span> Terjawab</span>
                            <span><span class="badge badge-circle w-10px h-10px" style="background:#E5E9F2"></span> Belum</span>
                        </div>
                        <button type="button" id="btnSubmit" class="btn btn-primary w-100"><i class="ki-outline ki-check fs-4"></i> Kumpulkan Ujian</button>
                    </div>
                </div>
            </div>

            {{-- ====== Area Soal (kanan/utama) ====== --}}
            <div class="col-lg-8 order-lg-1">
                @php
                    $subjectName = $exam->teachingAssignment->subject->name ?? 'Ujian';
                    $kelasName = $session->class_room_id
                        ? ($session->classRoom->name ?? null)
                        : ($exam->teachingAssignment->classRoom->name ?? null);
                    $typeLabel = ['mixed'=>'Pilihan Ganda + Essay','mc'=>'Pilihan Ganda','essay'=>'Essay'][$exam->type] ?? 'Ujian';
                @endphp
                <div class="card shadow-sm mb-5" style="border-left:6px solid #4F46E5">
                    <div class="card-body py-5">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                            <div class="flex-grow-1">
                                <span class="badge badge-light-primary fs-7 mb-2"><i class="ki-outline ki-book fs-6 me-1"></i> {{ $subjectName }}</span>
                                <h1 class="fw-bold text-gray-900 mb-2" style="font-size:clamp(26px,3.2vw,44px);letter-spacing:-.02em">{{ $exam->title }}</h1>
                                <div class="d-flex flex-wrap align-items-center gap-3 text-gray-700 fs-6">
                                    @if($kelasName)
                                        <span><i class="ki-outline ki-people fs-5 text-primary me-1"></i> Kelas <b>{{ $kelasName }}</b></span>
                                    @else
                                        <span><i class="ki-outline ki-people fs-5 text-primary me-1"></i> Peserta lintas kelas</span>
                                    @endif
                                    <span class="text-muted">•</span>
                                    <span><i class="ki-outline ki-calendar fs-5 text-primary me-1"></i> {{ $session->name }}</span>
                                    <span class="text-muted">•</span>
                                    <span><i class="ki-outline ki-document fs-5 text-primary me-1"></i> {{ $questions->count() }} soal ({{ $typeLabel }})</span>
                                </div>
                                <div class="fs-8 text-muted mt-1"><span id="saveStatus" class="text-success"></span></div>
                            </div>
                            <span class="badge badge-primary fs-5 py-3 px-4" id="qcounter">Soal 1 / {{ $questions->count() }}</span>
                        </div>
                    </div>
                </div>

                @php $no = 0; @endphp
                @foreach($questions as $q)
                @php
                    $no++;
                    $ans = $answers->get($q->id);
                    $opts = $q->type === 'mc' ? $q->options : collect();
                @endphp
                <div class="card mb-4 exam-q" data-qindex="{{ $no-1 }}" style="{{ $no===1 ? '' : 'display:none' }}">
                    <div class="card-body">
                        <div class="d-flex mb-4">
                            <span class="badge badge-circle badge-primary fs-5 me-3">{{ $no }}</span>
                            <div class="fw-semibold text-gray-900 fs-4">{!! nl2br(e($q->question_text)) !!}</div>
                        </div>
                        @if($q->image_path)<img src="{{ asset('storage/'.$q->image_path) }}" class="mb-4 d-block" alt="Gambar soal">@endif

                        @if($q->type === 'mc')
                            <div class="d-flex flex-column gap-2">
                                @foreach($opts as $opt)
                                <label class="d-flex align-items-center border rounded p-4 opt-label {{ $ans && $ans->selected_option_id === $opt->id ? 'border-primary bg-light-primary' : 'border-gray-300' }}">
                                    <input class="form-check-input me-3 ans-mc" type="radio" name="q_{{ $q->id }}" value="{{ $opt->id }}"
                                        data-question="{{ $q->id }}" {{ $ans && $ans->selected_option_id === $opt->id ? 'checked' : '' }}>
                                    {{-- Label MENGIKUTI POSISI TAMPIL (A,B,C,...), bukan label asli DB.
                                         Kalau opsi diacak, huruf asli tak boleh bocor — jawaban benar tetap
                                         ditentukan oleh option id, jadi penilaian tidak terpengaruh. --}}
                                    <span class="badge badge-light-primary me-3">{{ chr(65 + $loop->index) }}</span>
                                    <span class="d-flex flex-column">
                                        @if($opt->option_text)<span class="text-gray-800 fs-5">{{ $opt->option_text }}</span>@endif
                                        @if($opt->image_path)<img src="{{ asset('storage/'.$opt->image_path) }}" class="rounded mt-1 mh-150px" alt="Gambar opsi {{ chr(65 + $loop->index) }}">@endif
                                    </span>
                                </label>
                                @endforeach
                            </div>
                        @else
                            <div class="rdev-math-scope">
                                <textarea class="form-control ans-essay math-input" rows="6" data-question="{{ $q->id }}" data-preview="#prev_essay_{{ $q->id }}" placeholder="Tulis jawaban Anda di sini... (rumus: tulis di antara $ … $, atau unggah foto)">{{ $ans->answer_text ?? '' }}</textarea>
                                <div class="math-hint">Pratinjau rumus (yang ditulis antara tanda $) akan tampil di bawah:</div>
                                <div class="math-preview" id="prev_essay_{{ $q->id }}"></div>
                            </div>
                            <div class="mt-3">
                                {{-- Unggah foto HANYA untuk perangkat berlayar sentuh (HP/tablet):
                                     capture=environment membuat kamera langsung terbuka sehingga siswa tidak
                                     bisa memakai foto siapan dari galeri. Di laptop/PC atribut capture
                                     DIABAIKAN browser (berubah jadi pemilih berkas biasa) — itu celah contek,
                                     jadi seluruh blok ini disembunyikan lewat JS di perangkat non-sentuh. --}}
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2 js-photo-tools">
                                    <label class="btn btn-sm btn-primary mb-0">
                                        <i class="ki-outline ki-camera fs-5"></i> <span class="js-cam-label">Ambil Foto (Kamera)</span>
                                        <input type="file" accept="image/jpeg,image/jpg,image/png" capture="environment" class="d-none ans-photo" data-question="{{ $q->id }}">
                                    </label>
                                    <span class="text-muted fs-8">Maks 3 foto, JPG/JPEG/PNG. Foto <b>otomatis dikecilkan</b> di HP sebelum dikirim (≤ 1 MB). <b class="js-cam-note">Wajib foto langsung dari kamera</b> — cocok untuk rumus/coretan/diagram.</span>
                                </div>
                                <div class="d-flex flex-wrap gap-2 photo-list" id="photos_{{ $q->id }}">
                                    @foreach(($ans->answer_images ?? []) as $img)
                                    @php
                                        // Pratinjau pakai thumbnail bila tersedia supaya halaman tidak berat.
                                        $thumb = preg_replace('/\.[^.]+$/', '', $img).'_thumb.jpg';
                                        $thumbUrl = \Illuminate\Support\Facades\Storage::disk('public')->exists($thumb) ? asset('storage/'.$thumb) : asset('storage/'.$img);
                                    @endphp
                                    <div class="position-relative rdev-thumb" data-path="{{ $img }}">
                                        <img src="{{ $thumbUrl }}" data-full="{{ asset('storage/'.$img) }}" loading="lazy" alt="Foto jawaban">
                                        <button type="button" class="photo-del" data-question="{{ $q->id }}" data-path="{{ $img }}" title="Hapus">&times;</button>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                @endforeach

                {{-- Prev / Next --}}
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <button type="button" id="prevBtn" class="btn btn-light-primary"><i class="ki-outline ki-arrow-left fs-4"></i> Sebelumnya</button>
                    <button type="button" id="nextBtn" class="btn btn-primary">Berikutnya <i class="ki-outline ki-arrow-right fs-4"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Gerbang Layar Penuh (mode ujian) --}}
<div id="fsGate" style="position:fixed;inset:0;z-index:3000;background:linear-gradient(180deg,#0B1F3A,#142C52);display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;color:#fff;padding:24px">
    <i class="ki-outline ki-screen fs-5x text-white mb-4"></i>
    <h2 id="fsTitle" class="text-white fw-bold mb-2">Mode Ujian — Layar Penuh</h2>
    <p id="fsDesc" class="text-white opacity-75 mb-6" style="max-width:480px">Ujian berlangsung dalam layar penuh. Klik tombol di bawah untuk mulai, dan jangan keluar dari layar penuh sampai ujian selesai.</p>
    <button type="button" id="fsEnter" class="btn btn-light btn-lg fw-bold"><i class="ki-outline ki-rocket fs-3"></i> Masuk &amp; Mulai Ujian</button>
    <p class="text-white opacity-50 fs-8 mt-8">Darurat keluar: tekan <b>Ctrl + Shift + C</b></p>
</div>

{{-- Pratinjau gambar diperbesar: gambar soal, gambar opsi PG, & foto jawaban bisa diketuk.
     Menutupnya sengaja dibuat mudah: tombol × besar, ketuk gambarnya, atau ketuk area gelap.
     TIDAK memakai tombol Esc — di mode layar penuh Esc justru keluar fullscreen dan
     akan mengunci sesi ujian. --}}
{{-- Gerbang Lanjut Foto: muncul saat layar penuh terlepas KARENA membuka kamera/galeri
     (aksi sah untuk jawaban essay). Tidak perlu PIN — cukup satu ketukan untuk kembali
     ke layar penuh; browser memang mewajibkan gestur pengguna untuk masuk fullscreen. --}}
{{-- Peringatan tenggang: muncul saat siswa keluar layar ujian. Selama hitungan belum
     habis, sesi BELUM dikunci — supaya keluar sekejap (mis. notifikasi/salah tekan)
     tidak langsung memerlukan PIN guru. Setiap kejadian tetap DICATAT untuk guru. --}}
<div id="graceBar" style="position:fixed;left:0;right:0;top:0;z-index:3450;display:none;background:linear-gradient(90deg,#b45309,#f59e0b);color:#fff;padding:12px 16px;text-align:center;font-weight:600;box-shadow:0 6px 18px rgba(0,0,0,.2)">
    <span id="graceMsg">Kembali ke ujian sekarang.</span>
    <b> Sesi terkunci dalam <span id="graceCount">10</span> detik.</b>
    <button type="button" id="graceResume" class="btn btn-sm btn-light fw-bold ms-3">Lanjutkan Ujian</button>
    <div class="fs-8 fw-normal mt-1 opacity-75">Kejadian ini dicatat dan dilaporkan ke guru. Sisa toleransi: <span id="graceLeft">-</span>×</div>
</div>

<div id="camGate" style="position:fixed;inset:0;z-index:3400;background:linear-gradient(180deg,#0B1F3A,#142C52);flex-direction:column;align-items:center;justify-content:center;text-align:center;color:#fff;padding:24px;display:none">
    <i class="ki-outline ki-picture fs-5x text-white mb-4"><span class="path1"></span><span class="path2"></span></i>
    <h2 class="text-white fw-bold mb-2">Lanjutkan Ujian</h2>
    <p class="text-white opacity-75 mb-6" style="max-width:520px">Kamu baru saja membuka kamera/galeri untuk foto jawaban. Ini <b>bukan pelanggaran</b> — timer tetap berjalan. Ketuk tombol di bawah untuk kembali ke layar penuh.</p>
    <button type="button" id="camResume" class="btn btn-light btn-lg fw-bold"><i class="ki-outline ki-arrow-right fs-3"></i> Kembali ke Ujian</button>
</div>

{{-- Gerbang Kunci (anti-contek): muncul saat siswa keluar layar ujian; butuh PIN dari guru --}}
<div id="lockGate" style="position:fixed;inset:0;z-index:3500;background:linear-gradient(180deg,#7f1d1d,#450a0a);flex-direction:column;align-items:center;justify-content:center;text-align:center;color:#fff;padding:24px;display:{{ ($isLocked ?? false) ? 'flex' : 'none' }}">
    <i class="ki-outline ki-lock-2 fs-5x text-white mb-4"><span class="path1"></span><span class="path2"></span></i>
    <h2 class="text-white fw-bold mb-2">Sesi Terkunci</h2>
    <p class="text-white opacity-75 mb-4" style="max-width:520px">Kamu terdeteksi meninggalkan layar ujian (pindah tab/aplikasi atau keluar layar penuh). <b>Timer dijeda.</b> Untuk melanjutkan, minta <b>PIN</b> kepada pengawas/guru.</p>
    <p id="lockExtra" class="text-warning fw-bold mb-3" style="max-width:560px;display:none"></p>
    <div style="max-width:320px;width:100%">
        <input id="lockPin" type="text" inputmode="numeric" autocomplete="off" maxlength="10" class="form-control form-control-lg text-center mb-2" placeholder="Masukkan PIN" style="letter-spacing:.3em;font-weight:700">
        <div id="lockErr" class="text-warning fw-semibold mb-3" style="min-height:20px"></div>
        <button type="button" id="lockUnlock" class="btn btn-light btn-lg w-100 fw-bold"><i class="ki-outline ki-lock-3 fs-3"></i> Lanjutkan Ujian</button>
        <button type="button" id="lockExit" class="btn btn-outline-light btn-lg w-100 fw-bold mt-3"><i class="ki-outline ki-exit-right fs-3"></i> Keluar &amp; Selesai Ujian</button>
    </div>
    <p class="text-white opacity-50 fs-8 mt-8">Pelanggaran tercatat. Darurat keluar: Ctrl + Shift + C</p>
</div>

@push('scripts')
<script>
    /* ===== Mode ujian: layar penuh + proteksi (KHUSUS halaman ini) ===== */
    let examExiting = false;
    const EXIT_URL = "{{ route('student.exams.index') }}";

    function fsRequest(){ const el=document.documentElement; const fn=el.requestFullscreen||el.webkitRequestFullscreen||el.msRequestFullscreen; if(fn){ try{ fn.call(el); }catch(e){} } }
    function fsExit(){ const fn=document.exitFullscreen||document.webkitExitFullscreen||document.msExitFullscreen; if((document.fullscreenElement||document.webkitFullscreenElement)&&fn){ try{ fn.call(document); }catch(e){} } }
    function isFs(){ return !!(document.fullscreenElement||document.webkitFullscreenElement); }
    var FS_SUPPORTED = !!(document.documentElement.requestFullscreen || document.documentElement.webkitRequestFullscreen || document.documentElement.msRequestFullscreen);

    // Darurat keluar (Ctrl+Shift+C): keluar fullscreen, tutup tab; jika gagal, balik ke daftar ujian.
    function emergencyExit(){ examExiting = true; fsExit(); try{ window.close(); }catch(e){} setTimeout(()=>{ window.location.href = EXIT_URL; }, 150); }

    document.addEventListener('contextmenu', function(e){ e.preventDefault(); });
    document.addEventListener('keydown', function(e){
        var k = (e.key || '').toUpperCase();
        if (e.ctrlKey && e.shiftKey && k === 'C'){ e.preventDefault(); emergencyExit(); return false; } // darurat keluar
        if (e.key === 'F12' || e.keyCode === 123) { e.preventDefault(); return false; }                 // F12
        if (e.ctrlKey && e.shiftKey && (k === 'I' || k === 'J')) { e.preventDefault(); return false; }   // dev tools
        if (e.ctrlKey && k === 'U') { e.preventDefault(); return false; }                                // view-source
    });

    var _csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const CSRF = _csrfMeta ? _csrfMeta.getAttribute('content') : '';
    const SAVE_URL = "{{ route('student.exam-answers.save') }}";
    const ATTEMPT_ID = "{{ $attempt->id }}";
    let remaining = {{ (int) $remaining }};
    let submitting = false;
    const LOCK_URL = "{{ route('student.exams.lock') }}";
    const UNLOCK_URL = "{{ route('student.exams.unlock') }}";
    let locked = {{ ($isLocked ?? false) ? 'true' : 'false' }};
    let started = {{ ($isLocked ?? false) ? 'true' : 'false' }};

    /* ---------- Navigasi soal (satu per layar) ---------- */
    const cards = Array.from(document.querySelectorAll('.exam-q'));
    const navBtns = Array.from(document.querySelectorAll('.qnav'));
    const total = cards.length;
    let current = 0;

    function render(){
        cards.forEach((c, i) => c.style.display = (i === current ? 'block' : 'none'));
        navBtns.forEach((b, i) => b.classList.toggle('current', i === current));
        document.getElementById('prevBtn').disabled = (current === 0);
        const nb = document.getElementById('nextBtn');
        nb.style.visibility = (current === total - 1) ? 'hidden' : 'visible';
        document.getElementById('qcounter').textContent = `Soal ${current + 1} / ${total}`;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    function go(i){ if (i >= 0 && i < total){ current = i; render(); } }
    document.getElementById('prevBtn').addEventListener('click', () => go(current - 1));
    document.getElementById('nextBtn').addEventListener('click', () => go(current + 1));
    navBtns.forEach((b, i) => b.addEventListener('click', () => go(i)));
    render();

    function markAnswered(qid, val){
        const b = document.querySelector(`.qnav[data-qid="${qid}"]`);
        if (b) b.classList.toggle('answered', !!val);
        document.getElementById('answeredCount').textContent = document.querySelectorAll('.qnav.answered').length;
    }
    document.getElementById('answeredCount').textContent = document.querySelectorAll('.qnav.answered').length;

    /* ---------- Timer ---------- */
    const timerEl = document.getElementById('timer');
    function fmt(s){ const m = Math.floor(s/60), x = s%60; return String(m).padStart(2,'0')+':'+String(x).padStart(2,'0'); }
    function tick(){
        if (locked) return;                        // timer dijeda saat sesi terkunci
        if (remaining <= 0){ timerEl.textContent = '00:00'; autoSubmit(); return; }
        timerEl.textContent = fmt(remaining);
        remaining--;
    }
    tick(); const timerInt = setInterval(tick, 1000);

    /* ---------- Autosave ---------- */
    const status = document.getElementById('saveStatus');
    function setStatus(t, ok=true){ status.textContent = t; status.className = 'ms-2 ' + (ok ? 'text-success' : 'text-danger'); }
    function save(payload){
        return fetch(SAVE_URL, {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify(Object.assign({attempt_id: ATTEMPT_ID}, payload))
        }).then(async res => {
            // Dulu hanya status 422 yang dianggap gagal, sehingga kegagalan lain
            // (419 sesi berakhir, 404, 500) tetap dilaporkan "✓ Tersimpan" padahal
            // jawaban TIDAK tersimpan. Sekarang keberhasilan ditentukan oleh res.ok
            // dan alasan gagalnya ditampilkan apa adanya.
            if (res.ok){ setStatus('✓ Tersimpan'); return; }
            var d = {};
            try { d = await res.json(); } catch (e) {}
            if (res.status === 422 && d.expired){ clearInterval(timerInt); autoSubmit(); return; }
            if (res.status === 422 && d.locked){ showLockOverlay(); setStatus('Sesi terkunci — minta PIN pengawas', false); return; }
            if (res.status === 419){ setStatus('Sesi login berakhir — muat ulang halaman', false); return; }
            setStatus(d.message ? ('Gagal: ' + d.message) : ('Gagal menyimpan (kode ' + res.status + ')'), false);
        }).catch(() => setStatus('Gagal menyimpan — periksa koneksi', false));
    }

    document.querySelectorAll('.ans-mc').forEach(r => {
        r.addEventListener('change', function(){
            document.querySelectorAll(`input[name="q_${this.dataset.question}"]`).forEach(x => x.closest('.opt-label').classList.remove('border-primary','bg-light-primary'));
            this.closest('.opt-label').classList.add('border-primary','bg-light-primary');
            setStatus('Menyimpan...');
            markAnswered(this.dataset.question, true);
            save({question_id: this.dataset.question, selected_option_id: this.value});
        });
    });

    let timers = {};
    function updateEssayAnswered(qid){
        var ta = document.querySelector('.ans-essay[data-question="'+qid+'"]');
        var hasText = ta && ta.value.trim() !== '';
        var box = document.getElementById('photos_'+qid);
        var hasPhoto = box && box.children.length > 0;
        markAnswered(qid, hasText || hasPhoto);
    }
    document.querySelectorAll('.ans-essay').forEach(t => {
        t.addEventListener('input', function(){
            const qid = this.dataset.question, val = this.value;
            updateEssayAnswered(qid);
            setStatus('Mengetik...');
            clearTimeout(timers[qid]);
            timers[qid] = setTimeout(() => { setStatus('Menyimpan...'); save({question_id: qid, answer_text: val}); }, 900);
        });
    });

    /* ---------- Foto jawaban essay ---------- */
    const PHOTO_URL = "{{ route('student.exam-answers.photo') }}";
    const PHOTO_DEL_URL = "{{ route('student.exam-answers.photo.delete') }}";
    function renderPhotos(qid, images){
        var box = document.getElementById('photos_'+qid); if (!box) return;
        box.innerHTML = (images || []).map(function(im){
            // Grid memakai thumbnail (ringan); versi penuh dibuka saat gambar diketuk.
            return '<div class="position-relative rdev-thumb" data-path="'+im.path+'"><img src="'+(im.thumb || im.url)+'" data-full="'+im.url+'" loading="lazy" alt="Foto"><button type="button" class="photo-del" data-question="'+qid+'" data-path="'+im.path+'" title="Hapus">&times;</button></div>';
        }).join('');
        updateEssayAnswered(qid);
    }
    // Foto yang gagal diunggah karena sesi sedang terkunci disimpan dulu, lalu dikirim
    // otomatis setelah PIN dibuka — supaya jepretan siswa tidak hilang begitu saja.
    const pendingPhotos = [];
    /* Petak sementara + pemutar (spinner) selama foto dikirim ke server, memakai
       pratinjau lokal dari kamera supaya siswa tahu jepretannya sedang diproses. */
    function tambahPemuatFoto(qid, file){
        var box = document.getElementById('photos_'+qid);
        if (!box) return null;
        var el = document.createElement('div');
        el.className = 'position-relative rdev-thumb foto-memuat';
        var pratinjau = '';
        try { pratinjau = '<img src="'+URL.createObjectURL(file)+'" alt="Foto sedang diunggah">'; } catch(e){}
        el.innerHTML = pratinjau + '<div class="foto-spinner"><span class="spinner-border spinner-border-sm text-white" role="status" aria-hidden="true"></span><span class="foto-spinner-teks">Mengunggah…</span></div>';
        box.appendChild(el);
        updateEssayAnswered(qid);
        return el;
    }
    function hapusPemuatFoto(el){
        if (!el) return;
        var img = el.querySelector('img');
        if (img && img.src && img.src.indexOf('blob:') === 0) { try { URL.revokeObjectURL(img.src); } catch(e){} }
        if (el.parentNode) el.parentNode.removeChild(el);
    }
    /* Kecilkan foto DI HP sebelum dikirim. Jepretan kamera bisa 3–12 MB sehingga
       ditolak server (batas 1 MB) dan lambat diunggah lewat data seluler. Gambar
       digambar ulang ke kanvas (sisi terpanjang maks 1600px) lalu disimpan sebagai
       JPEG; mutunya diturunkan bertahap sampai ukurannya di bawah target. */
    const TARGET_BYTES = 950 * 1024;   // aman di bawah batas 1 MB milik server
    function kompresGambar(file){
        return new Promise(function(resolve){
            try {
                if (!file || !/^image\//.test(file.type || '')) return resolve(file);
                var url = URL.createObjectURL(file);
                var img = new Image();
                img.onload = function(){
                    try {
                        var maks = 1600;
                        var s = Math.min(1, maks / Math.max(img.width, img.height));
                        var w = Math.max(1, Math.round(img.width * s));
                        var h = Math.max(1, Math.round(img.height * s));
                        var c = document.createElement('canvas'); c.width = w; c.height = h;
                        var ctx = c.getContext('2d');
                        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, w, h);   // PNG transparan → putih
                        ctx.drawImage(img, 0, 0, w, h);
                        URL.revokeObjectURL(url);
                        var mutu = [0.75, 0.6, 0.45, 0.35], i = 0;
                        var coba = function(){
                            c.toBlob(function(blob){
                                if (!blob) return resolve(file);                       // gagal → kirim aslinya
                                if (blob.size <= TARGET_BYTES || i >= mutu.length - 1) return resolve(blob);
                                i++; coba();
                            }, 'image/jpeg', mutu[i]);
                        };
                        coba();
                    } catch(e){ resolve(file); }
                };
                img.onerror = function(){ try { URL.revokeObjectURL(url); } catch(e){} resolve(file); };
                img.src = url;
            } catch(e){ resolve(file); }
        });
    }
    function uploadPhoto(qid, file, pemuat){
        var fd = new FormData(); fd.append('attempt_id', ATTEMPT_ID); fd.append('question_id', qid);
        fd.append('photo', file, (file && file.name) ? file.name : 'foto.jpg');   // Blob perlu nama berkas
        setStatus('Mengunggah foto...');
        if (!pemuat) pemuat = tambahPemuatFoto(qid, file);
        return fetch(PHOTO_URL, { method:'POST', headers:{'X-CSRF-TOKEN':CSRF, 'Accept':'application/json'}, body:fd })
            .finally(function(){ hapusPemuatFoto(pemuat); })
            .then(async r => { var d = await r.json();
                if (!r.ok){
                    if (d.expired){ clearInterval(timerInt); autoSubmit(); return; }
                    if (d.locked){                       // simpan; kirim ulang setelah unlock
                        pendingPhotos.push({ qid: qid, file: file });
                        setStatus('Foto menunggu sesi dibuka', false);
                        return;
                    }
                    Swal.fire('Gagal', d.message || 'Gagal unggah foto', 'error'); setStatus('Gagal unggah', false); return;
                }
                renderPhotos(qid, d.images); setStatus('✓ Foto tersimpan');
            }).catch(() => { pendingPhotos.push({ qid: qid, file: file }); setStatus('Gagal unggah — akan dicoba lagi', false); });
    }
    function flushPendingPhotos(){
        if (!pendingPhotos.length) return;
        var queue = pendingPhotos.splice(0, pendingPhotos.length);
        queue.reduce(function(p, item){ return p.then(function(){ return uploadPhoto(item.qid, item.file); }); }, Promise.resolve());
    }
    document.querySelectorAll('.ans-photo').forEach(inp => {
        inp.addEventListener('change', function(){
            if (!this.files || !this.files[0]) return;
            var qid = this.dataset.question, asli = this.files[0];
            this.value = '';
            // Spinner langsung tampil: proses mengecilkan foto besar bisa 1–2 detik.
            var pemuat = tambahPemuatFoto(qid, asli);
            var teks = pemuat && pemuat.querySelector('.foto-spinner-teks');
            if (teks) teks.textContent = 'Menyiapkan…';
            setStatus('Menyiapkan foto...');
            kompresGambar(asli).then(function(siap){
                if (teks) teks.textContent = 'Mengunggah…';
                uploadPhoto(qid, siap, pemuat);
            });
        });
    });
    // Unggah foto jawaban hanya tersedia di perangkat berlayar sentuh (HP/tablet), karena
    // di sana capture=environment memaksa kamera. Di laptop/PC capture diabaikan browser
    // sehingga akan berubah menjadi pemilih berkas (celah memakai foto siapan) → blok
    // unggah disembunyikan sepenuhnya. Foto yang sudah pernah diunggah dari HP tetap tampil.
    (function(){
        var isTouch = ('ontouchstart' in window) || (navigator.maxTouchPoints || 0) > 0;
        if (isTouch) return;
        document.querySelectorAll('.js-photo-tools').forEach(function(el){ el.remove(); });
    })();
    document.addEventListener('click', function(e){
        var b = e.target.closest && e.target.closest('.photo-del'); if (!b) return;
        var qid = b.dataset.question, path = b.dataset.path;
        var fd = new FormData(); fd.append('attempt_id', ATTEMPT_ID); fd.append('question_id', qid); fd.append('path', path);
        fetch(PHOTO_DEL_URL, { method:'POST', headers:{'X-CSRF-TOKEN':CSRF, 'Accept':'application/json'}, body:fd })
            .then(async r => { var d = await r.json(); renderPhotos(qid, d.images); });
    });

    /* ---------- Submit ---------- */
    // Semua jalur penyelesaian ujian (submit manual, waktu habis, dan tombol
    // "Keluar & selesai" di layar terkunci) bermuara ke HALAMAN tanda tangan.
    // Soal tidak lagi dirender di sana, jadi siswa tidak bisa membaca atau
    // menjawab lagi setelah menyatakan selesai.
    var menujuTtd = false;
    function keTandaTangan(){
        menujuTtd = true;
        submitting = true;          // matikan peringatan "beforeunload"
        clearInterval(timerInt);
        fsExit();
        window.location.href = SIGN_URL;
    }
    var SIGN_URL = "{{ route('student.exams.sign', $session->id) }}";
    function autoSubmit(){
        if (menujuTtd) return;
        menujuTtd = true;
        submitting = true;
        clearInterval(timerInt);
        Swal.fire({title:'Waktu Habis!', text:'Anda diarahkan ke halaman tanda tangan.',
            icon:'info', allowOutsideClick:false, timer:2000, timerProgressBar:true})
            .then(keTandaTangan);
    }
    function confirmSubmit(){
        const unanswered = total - document.querySelectorAll('.qnav.answered').length;
        Swal.fire({
            title:'Kumpulkan ujian?',
            html: unanswered > 0 ? `Masih ada <b>${unanswered} soal</b> belum dijawab. Tetap kumpulkan?` : 'Semua soal sudah dijawab. Kumpulkan sekarang?',
            icon: unanswered > 0 ? 'warning' : 'question',
            showCancelButton:true, confirmButtonText:'Ya, Kumpulkan', cancelButtonText:'Cek lagi', confirmButtonColor:'#4F46E5'
        }).then(r => { if (r.isConfirmed) keTandaTangan(); });
    }

    document.getElementById('btnSubmit').addEventListener('click', confirmSubmit);

    window.addEventListener('beforeunload', function(e){ if (!submitting && !examExiting){ e.preventDefault(); e.returnValue=''; } });

    /* ---------- Gerbang & penegakan layar penuh ---------- */
    const fsGate = document.getElementById('fsGate');
    document.getElementById('fsEnter').addEventListener('click', function(){
        if (!FS_SUPPORTED){ started = true; fsGate.style.display = 'none'; return; }  // iPhone/Safari: mulai tanpa fullscreen
        fsRequest();
    });
    function onFsChange(){
        if (examExiting || submitting) return;
        if (isFs()){
            started = true;                 // sudah masuk mode ujian
            fsGate.style.display = 'none';
            if (typeof hideCamGate === 'function') hideCamGate();
            if (typeof batalTenggang === 'function') batalTenggang(false);  // kembali fullscreen → aman
        } else if (!started){
            // Belum pernah masuk fullscreen → tampilkan gerbang masuk.
            fsGate.style.display = 'flex';
        } else if (pickingFile){
            // Fullscreen lepas KARENA membuka kamera/galeri (foto jawaban essay) → BUKAN pelanggaran.
            // Langsung lanjut mengerjakan (tanpa gerbang & tanpa PIN); layar penuh dipulihkan
            // otomatis pada ketukan berikutnya karena browser mewajibkan gestur pengguna.
            armFsOnNextTap();
        } else if (!locked){
            // Keluar layar penuh (mis. tekan Esc) → beri tenggang untuk kembali dulu.
            mulaiTenggang('Kamu keluar dari mode layar penuh.');
        }
    }
    document.addEventListener('fullscreenchange', onFsChange);
    document.addEventListener('webkitfullscreenchange', onFsChange);

    /* ---------- Anti-contek: kunci sesi saat keluar layar ujian ---------- */
    const lockGate = document.getElementById('lockGate');
    function showLockOverlay(extra){
        locked = true; if (lockGate) lockGate.style.display = 'flex';
        var ex = document.getElementById('lockExtra');
        if (ex){ if (extra){ ex.textContent = extra; ex.style.display = 'block'; } else { ex.style.display = 'none'; } }
    }
    function sendLockBeacon(){
        try {
            var fd = new FormData();
            fd.append('_token', CSRF);
            fd.append('attempt_id', ATTEMPT_ID);
            if (navigator.sendBeacon) navigator.sendBeacon(LOCK_URL, fd);
            else fetch(LOCK_URL, {method:'POST', headers:{'X-CSRF-TOKEN':CSRF}, body:fd, keepalive:true});
        } catch(e){}
    }
    function doLock(){
        if (submitting || examExiting || locked || !started) return;
        if (typeof graceT !== 'undefined' && graceT){ clearTimeout(graceT); graceT = null; }
        if (typeof hideGrace === 'function') hideGrace();   // banner tenggang tak perlu lagi
        showLockOverlay();       // tampilkan overlay dulu (instan)
        sendLockBeacon();        // catat di server (andal walau tab disembunyikan)
    }
    // ===== Deteksi LAYAR KEDUA (extended display: HDMI ke monitor/proyektor kedua, atau mirroring/cast) =====
    // screen.isExtended = true saat ada >1 layar dalam mode diperluas. 1 monitor (incl. HDMI tunggal) = false.
    function isMultiScreen(){
        try { if (window.screen && typeof window.screen.isExtended === 'boolean') return window.screen.isExtended === true; } catch(e){}
        return false;
    }
    var MULTI_MSG = 'Terdeteksi LAYAR KEDUA / tampilan diperluas (HDMI ke monitor-proyektor kedua, atau screen mirroring/cast). Demi keamanan ujian: cabut kabel HDMI / hentikan cast — sisakan 1 layar — lalu minta PIN pengawas untuk lanjut.';
    function checkScreens(){
        if (submitting || examExiting || !started) return;
        if (isMultiScreen() && !locked){ showLockOverlay(MULTI_MSG); sendLockBeacon(); }
    }
    try { if (window.screen && window.screen.addEventListener) window.screen.addEventListener('change', checkScreens); } catch(e){}
    setInterval(checkScreens, 4000);
    setTimeout(checkScreens, 1200);
    function doUnlock(){
        var input = document.getElementById('lockPin');
        var errEl = document.getElementById('lockErr');
        var pin = (input.value || '').trim();
        if (!pin){ errEl.textContent = 'Masukkan PIN dulu.'; return; }
        if (isMultiScreen()){ errEl.textContent = 'Masih terdeteksi layar kedua. Cabut HDMI / hentikan cast dulu (sisakan 1 layar).'; return; }
        errEl.textContent = '';
        var btn = document.getElementById('lockUnlock');
        var old = btn.innerHTML; btn.classList.add('disabled'); btn.innerHTML = 'Memeriksa...';
        var fd = new FormData(); fd.append('attempt_id', ATTEMPT_ID); fd.append('pin', pin);
        fetch(UNLOCK_URL, {method:'POST', headers:{'X-CSRF-TOKEN':CSRF, 'Accept':'application/json'}, body:fd})
            .then(async r => {
                var d = await r.json();
                btn.classList.remove('disabled'); btn.innerHTML = old;
                if (!r.ok){ errEl.textContent = d.message || 'PIN salah.'; input.value = ''; return; }
                locked = false;
                if (typeof d.remaining === 'number') remaining = d.remaining;
                input.value = '';
                lockGate.style.display = 'none';
                flushPendingPhotos();   // kirim ulang foto yang tertahan saat sesi terkunci
                fsRequest();     // kembali ke layar penuh (gestur klik tombol ini valid)
            })
            .catch(() => { btn.classList.remove('disabled'); btn.innerHTML = old; errEl.textContent = 'Gagal terhubung. Coba lagi.'; });
    }
    if (lockGate){
        document.getElementById('lockUnlock').addEventListener('click', doUnlock);
        document.getElementById('lockPin').addEventListener('keydown', function(e){ if (e.key === 'Enter'){ e.preventDefault(); doUnlock(); } });
        var lockExitBtn = document.getElementById('lockExit');
        if (lockExitBtn) lockExitBtn.addEventListener('click', function(){
            Swal.fire({
                title:'Keluar & selesai ujian?',
                html:'Ujian akan <b>dikumpulkan</b> dan <b>diakhiri sekarang</b>. Jawaban yang sudah terisi tetap tersimpan. Tindakan ini <b>tidak bisa dibatalkan</b>.',
                icon:'warning', showCancelButton:true,
                confirmButtonText:'Ya, keluar & selesai', cancelButtonText:'Batal',
                confirmButtonColor:'#dc2626', allowOutsideClick:false
            }).then(function(res){ if (res.isConfirmed){ keTandaTangan(); } });
        });
    }
    // Abaikan penyembunyian tab / keluar layar penuh akibat membuka kamera-galeri untuk foto jawaban.
    // Di HP, membuka kamera MENYEMBUNYIKAN tab SEKALIGUS MELEPAS fullscreen; keduanya harus dimaafkan,
    // kalau tidak sesi terkunci dan unggahan foto ditolak server (locked) sehingga foto hilang.
    const camGate = document.getElementById('camGate');
    const PICK_GRACE_MS = 120000;   // batas wajar memotret; lewat ini dianggap pelanggaran biasa
    let pickingFile = false, pickT = null;
    function beginPick(){
        pickingFile = true;
        clearTimeout(pickT);
        pickT = setTimeout(function(){ pickingFile = false; }, PICK_GRACE_MS);
    }
    function endPick(){ pickingFile = false; clearTimeout(pickT); }
    function showCamGate(){ if (camGate && !locked) camGate.style.display = 'flex'; }
    function hideCamGate(){ if (camGate) camGate.style.display = 'none'; }
    document.querySelectorAll('.ans-photo').forEach(function(inp){
        inp.addEventListener('click', beginPick);
    });
    if (camGate){
        document.getElementById('camResume').addEventListener('click', function(){
            hideCamGate(); endPick(); fsRequest();   // gestur klik → boleh masuk fullscreen lagi
        });
    }
    // Sepulang dari kamera, layar penuh ikut terlepas. Browser melarang masuk fullscreen
    // tanpa gestur pengguna, jadi siswa TIDAK dihadang gerbang: ujian langsung bisa
    // dilanjutkan, dan layar penuh dipulihkan diam-diam pada ketukan/klik berikutnya.
    var fsArmed = false;
    function armFsOnNextTap(){
        hideCamGate();
        if (fsArmed) return;
        fsArmed = true;
        var pulihkan = function(){
            fsArmed = false;
            document.removeEventListener('click', pulihkan, true);
            document.removeEventListener('touchend', pulihkan, true);
            if (started && !locked && !submitting && !examExiting && FS_SUPPORTED && !isFs()) fsRequest();
        };
        document.addEventListener('click', pulihkan, true);
        document.addEventListener('touchend', pulihkan, true);
        setStatus('Lanjutkan mengerjakan — layar penuh kembali otomatis');
    }
    // Kembali ke halaman setelah memotret: beri jeda agar event change (unggah foto)
    // sempat berjalan, lalu lepas status "sedang memotret".
    window.addEventListener('focus', function(){
        if (!pickingFile) return;
        setTimeout(function(){
            if (!pickingFile) return;
            endPick();
            if (started && !locked && !submitting && !examExiting && FS_SUPPORTED && !isFs()) armFsOnNextTap();
        }, 1500);
    });
    /* ---------- TOLERANSI KELUAR LAYAR UJIAN ----------
       Keluar sekejap (notifikasi, salah tekan, kamera) tidak langsung mengunci:
       ada tenggang GRACE_MS untuk kembali. Namun tiap kejadian DICATAT ke server
       (leave_count) dan ditampilkan ke guru, dan toleransi hanya MAX_TOLERANSI kali —
       setelah itu keluar = langsung terkunci. Timer ujian tetap berjalan saat keluar,
       jadi toleransi ini tidak bisa dipakai untuk mencuri waktu mencontek. */
    const LEAVE_URL = "{{ route('student.exams.leave-warning') }}";
    const GRACE_MS = 10000;        // tenggang kembali (10 detik)
    const MAX_TOLERANSI = 2;       // setelah 2 kali ditoleransi → keluar berikutnya mengunci
    let leaveCount = {{ (int) ($attempt->leave_count ?? 0) }};
    let graceT = null, graceTick = null;
    const graceBar = document.getElementById('graceBar');

    function sisaToleransi(){ return Math.max(0, MAX_TOLERANSI - leaveCount); }
    function tampilSisa(){ var el = document.getElementById('graceLeft'); if (el) el.textContent = sisaToleransi(); }
    function hideGrace(){
        if (graceBar) graceBar.style.display = 'none';
        clearInterval(graceTick); graceTick = null;
    }
    function showGrace(pesan){
        if (!graceBar) return;
        var m = document.getElementById('graceMsg'); if (m && pesan) m.textContent = pesan;
        tampilSisa();
        graceBar.style.display = 'block';
        var sisa = Math.round(GRACE_MS / 1000);
        var c = document.getElementById('graceCount'); if (c) c.textContent = sisa;
        clearInterval(graceTick);
        graceTick = setInterval(function(){
            sisa--; if (c) c.textContent = Math.max(0, sisa);
            if (sisa <= 0) clearInterval(graceTick);
        }, 1000);
    }
    function catatKeluar(){    // catat di server tanpa mengunci
        try {
            var fd = new FormData(); fd.append('_token', CSRF); fd.append('attempt_id', ATTEMPT_ID);
            fetch(LEAVE_URL, {method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body:fd, keepalive:true})
                .then(function(r){ return r.json(); })
                .then(function(d){ if (d && typeof d.leave_count === 'number'){ leaveCount = d.leave_count; tampilSisa(); } })
                .catch(function(){});
        } catch(e){}
    }
    function mulaiTenggang(pesan){
        if (submitting || examExiting || locked || !started || pickingFile || menujuTtd) return;
        if (sisaToleransi() <= 0){ doLock(); return; }      // toleransi habis → kunci langsung
        if (graceT) return;                                  // tenggang sudah berjalan
        showGrace(pesan);
        graceT = setTimeout(function(){
            graceT = null; hideGrace();
            doLock();                                        // tidak kembali → kunci (butuh PIN)
        }, GRACE_MS);
    }
    function batalTenggang(hitung){
        if (!graceT) return;
        clearTimeout(graceT); graceT = null; hideGrace();
        if (hitung){ leaveCount++; catatKeluar(); }          // ditoleransi, tetapi dicatat
    }
    if (graceBar){
        document.getElementById('graceResume').addEventListener('click', function(){
            batalTenggang(true);
            if (FS_SUPPORTED && !isFs()) fsRequest();        // gestur klik → boleh fullscreen lagi
        });
    }

    // Pemicu: pindah tab / minimize / beralih aplikasi → beri tenggang dulu.
    document.addEventListener('visibilitychange', function(){
        if (pickingFile) return;   // akibat pemilih file/kamera, bukan pindah tab
        if (document.hidden){
            mulaiTenggang('Kamu meninggalkan layar ujian.');
        } else {
            batalTenggang(true);   // kembali dalam tenggang → tidak dikunci, hanya dicatat
        }
    });
    // Pemicu kunci tambahan: JENDELA KEHILANGAN FOKUS (mis. Alt+Tab).
    // Firefox tidak menandai halaman "hidden" saat Alt+Tab selama masih layar penuh,
    // jadi visibilitychange di atas tidak jalan — hanya minimize (Win+D) yang terdeteksi.
    // Diberi jeda + cek document.hasFocus() agar blur sekejap (transisi layar penuh atau
    // membuka pemilih berkas) tidak salah dianggap pelanggaran.
    var blurLockT = null;
    window.addEventListener('blur', function(){
        if (pickingFile || submitting || examExiting || locked || !started) return;
        clearTimeout(blurLockT);
        blurLockT = setTimeout(function(){
            if (pickingFile || submitting || examExiting || locked || !started) return;
            if (document.hasFocus()) return;   // ternyata sudah kembali → bukan pindah aplikasi
            mulaiTenggang('Jendela ujian kehilangan fokus.');
        }, 400);
    });
    window.addEventListener('focus', function(){ batalTenggang(true); });

    // Muat ulang dalam keadaan terkunci → pastikan overlay tampil & anggap sudah mulai.
    if (locked){ started = true; if (lockGate) lockGate.style.display = 'flex'; }
</script>
@endpush

@include('partials.img-zoom', ['sel' => '.exam-q img, .rdev-thumb img', 'closeOnBlur' => true, 'z' => 2900])
@endsection
