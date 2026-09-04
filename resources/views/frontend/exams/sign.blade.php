@extends('frontend.layout.app')
@section('title', 'Tanda Tangan: ' . $session->exam->title)

@section('content')
{{-- Halaman ini SENGAJA tidak memuat soal. Siswa tiba di sini begitu waktu ujian
     berakhir, sehingga tidak bisa lagi membaca atau menjawab soal. Jawaban baru
     dikumpulkan dan dinilai setelah ditandatangani dari sini. --}}
<div class="app-content flex-column-fluid">
    <div class="app-container container-xxl py-6">
        <div class="row justify-content-center">
            <div class="col-lg-6">

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <div class="card mb-5">
                    <div class="card-body text-center py-8">
                        @if($waktuHabis)
                            <i class="ki-outline ki-timer fs-3x text-danger mb-4"></i>
                            <h2 class="fw-bold mb-2">Waktu Ujian Telah Berakhir</h2>
                        @else
                            <i class="ki-outline ki-check-circle fs-3x text-primary mb-4"></i>
                            <h2 class="fw-bold mb-2">Konfirmasi Selesai Ujian</h2>
                        @endif
                        <div class="text-muted fs-6">
                            Jawaban Anda <b>belum dikumpulkan</b>. Tanda tangani daftar hadir di bawah,
                            lalu tekan <b>Kumpulkan &amp; Selesai</b> agar ujian Anda dinilai.
                        </div>
                    </div>
                </div>

                <div class="card mb-5">
                    <div class="card-body py-5">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <div class="text-muted fs-8">Nama</div>
                                <div class="fw-bold fs-6">{{ $student->user->name ?? '-' }}</div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="text-muted fs-8">NISN</div>
                                <div class="fw-bold fs-6">{{ $student->nisn ?: '-' }}</div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="text-muted fs-8">Ujian</div>
                                <div class="fw-bold fs-6">{{ $session->exam->title }}</div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="text-muted fs-8">Sesi</div>
                                <div class="fw-bold fs-6">{{ $session->name }}</div>
                            </div>
                            <div class="col-12">
                                <div class="text-muted fs-8">Terjawab</div>
                                <div class="fw-bold fs-6">{{ $terjawab }} dari {{ $jumlahSoal }} soal</div>
                            </div>
                        </div>
                    </div>
                </div>

                <form action="{{ route('student.exams.submit', $session->id) }}" method="POST" id="formTtd">
                    @csrf
                    <input type="hidden" name="signature" id="ttdData">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="fw-bold mb-1">Tanda Tangan Daftar Hadir</h4>
                            <div class="text-muted fs-8 mb-3">Coret tanda tangan Anda menggunakan jari (di HP)
                                atau mouse.</div>

                            <div class="border border-2 border-gray-300 rounded position-relative"
                                style="background:#fff;touch-action:none">
                                <canvas id="ttdKanvas" style="width:100%;height:220px;display:block;touch-action:none"></canvas>
                                <div id="ttdPetunjuk" class="position-absolute top-50 start-50 translate-middle text-muted fs-7"
                                    style="pointer-events:none">Tanda tangan di sini</div>
                            </div>

                            <div class="d-flex gap-2 mt-4">
                                <button type="button" class="btn btn-light-danger" id="ttdHapus">
                                    <i class="ki-outline ki-eraser fs-5"></i></button>
                                @unless($waktuHabis)
                                    {{-- Masih ada waktu → siswa boleh kembali memeriksa jawabannya. --}}
                                    <a href="{{ route('student.exams.attempt', $session->id) }}"
                                        class="btn btn-light">Kembali ke soal</a>
                                @endunless
                                <button type="submit" class="btn btn-primary flex-grow-1" id="ttdKirim">
                                    <i class="ki-outline ki-check fs-4 me-1"></i>Kumpulkan &amp; Selesai
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var kanvas = document.getElementById('ttdKanvas');
        if (!kanvas) return;
        var ctx = kanvas.getContext('2d');
        var petunjuk = document.getElementById('ttdPetunjuk');
        var ada = false, gambar = false;

        function siapkan() {
            var r = window.devicePixelRatio || 1;
            var w = kanvas.clientWidth, h = kanvas.clientHeight;
            kanvas.width = Math.round(w * r);
            kanvas.height = Math.round(h * r);
            ctx.setTransform(r, 0, 0, r, 0, 0);
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, w, h);
            ctx.lineWidth = 2.2; ctx.lineCap = 'round'; ctx.lineJoin = 'round';
            ctx.strokeStyle = '#111827';
        }
        siapkan();
        window.addEventListener('resize', function () { ada = false; if (petunjuk) petunjuk.style.display = ''; siapkan(); });

        function pos(e) {
            var k = kanvas.getBoundingClientRect();
            var p = e.touches ? e.touches[0] : e;
            return { x: p.clientX - k.left, y: p.clientY - k.top };
        }
        ['mousedown', 'touchstart'].forEach(function (n) {
            kanvas.addEventListener(n, function (e) {
                e.preventDefault(); gambar = true;
                var p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y);
            }, { passive: false });
        });
        ['mousemove', 'touchmove'].forEach(function (n) {
            kanvas.addEventListener(n, function (e) {
                if (!gambar) return;
                e.preventDefault();
                var p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke();
                if (!ada) { ada = true; if (petunjuk) petunjuk.style.display = 'none'; }
            }, { passive: false });
        });
        ['mouseup', 'mouseleave', 'touchend', 'touchcancel'].forEach(function (n) {
            kanvas.addEventListener(n, function () { gambar = false; });
        });

        document.getElementById('ttdHapus').addEventListener('click', function () {
            ada = false; if (petunjuk) petunjuk.style.display = ''; siapkan();
        });

        document.getElementById('formTtd').addEventListener('submit', function (e) {
            if (!ada) {
                e.preventDefault();
                Swal.fire({ icon: 'warning', title: 'Tanda tangan belum ada', text: 'Coret tanda tangan Anda dulu.' });
                return;
            }
            document.getElementById('ttdData').value = kanvas.toDataURL('image/png');
            var b = document.getElementById('ttdKirim');
            b.setAttribute('data-kt-indicator', 'on');
            b.disabled = true;
            setTimeout(function () { b.closest('form').submit(); }, 0);
        });
    })();
</script>
@endpush
