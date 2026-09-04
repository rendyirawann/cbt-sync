@extends('backend.layout.app')
@section('title', 'Pantau: ' . $session->exam->title)

@section('content')
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0">{{ $session->exam->title }}</h1>
            <span class="text-muted fs-7 pt-1">
                Sesi <b>{{ $session->name }}</b> ·
                {{ $session->classRoom->name ?? 'peserta pilihan' }} ·
                {{ \Carbon\Carbon::parse($session->starts_at)->format('d M Y H:i') }}–{{ \Carbon\Carbon::parse($session->ends_at)->format('H:i') }}
            </span>
        </div>
        <a href="{{ route('exam-monitor.index') }}" class="btn btn-sm btn-light">Kembali</a>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-xxl">

        <div class="row g-3 mb-5">
            @foreach([
                ['belum','Belum masuk','light-danger'],
                ['kerja','Sedang mengerjakan','light-primary'],
                ['kunci','Terkunci','light-warning'],
                ['selesai','Sudah selesai','light-success'],
                ['total','Total peserta','light'],
            ] as [$k, $label, $warna])
                <div class="col-6 col-lg">
                    <div class="card"><div class="card-body py-4 text-center">
                        <div class="fs-2hx fw-bold" id="jml-{{ $k }}">{{ $ringkas['jumlah'][$k] }}</div>
                        <span class="badge badge-{{ $warna }} mt-2">{{ $label }}</span>
                    </div></div>
                </div>
            @endforeach
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title"><h3 class="fw-bold m-0">Peserta</h3></div>
                <div class="card-toolbar">
                    <span class="text-muted fs-8 me-3">diperbarui <span id="jamPerbarui">{{ $ringkas['diperbarui'] }}</span></span>
                    <label class="form-check form-check-sm">
                        <input class="form-check-input" type="checkbox" id="autoSegar" checked>
                        <span class="form-check-label fs-8 ms-2">Segarkan otomatis</span>
                    </label>
                </div>
            </div>
            <div class="card-body pt-2">
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle gs-0 gy-3">
                        <thead>
                            <tr class="fw-bold text-muted fs-8 text-uppercase">
                                <th>Nama</th><th>NISN</th><th>Status</th><th>Mulai</th>
                                <th>Kumpul</th><th>Sisa</th><th>Keluar/Kunci</th><th>Nilai</th><th>TTD</th>
                            </tr>
                        </thead>
                        <tbody id="barisPeserta"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var URL_DATA = "{{ route('exam-monitor.data', $session->id) }}";
        var awal = @json($ringkas);
        var tbody = document.getElementById('barisPeserta');
        var autoSegar = document.getElementById('autoSegar');

        var LABEL = {
            belum:   ['Belum masuk', 'badge-light-danger'],
            kerja:   ['Mengerjakan', 'badge-light-primary'],
            kunci:   ['Terkunci', 'badge-light-warning'],
            selesai: ['Selesai', 'badge-light-success']
        };

        function jam(d) {
            if (d === null || d === undefined) return '-';
            var m = Math.floor(d / 60), s = d % 60;
            return m + 'm ' + (s < 10 ? '0' : '') + s + 's';
        }
        function aman(v) {
            return String(v === null || v === undefined ? '-' : v)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        }
        function gambar(d) {
            document.getElementById('jamPerbarui').textContent = d.diperbarui;
            ['belum', 'kerja', 'kunci', 'selesai', 'total'].forEach(function (k) {
                var el = document.getElementById('jml-' + k);
                if (el) el.textContent = d.jumlah[k];
            });
            tbody.innerHTML = d.peserta.map(function (p) {
                var l = LABEL[p.status] || ['-', 'badge-light'];
                return '<tr>' +
                    '<td class="fw-semibold text-gray-900">' + aman(p.nama) + '</td>' +
                    '<td class="text-muted fs-7">' + aman(p.nisn) + '</td>' +
                    '<td><span class="badge ' + l[1] + '">' + l[0] + '</span></td>' +
                    '<td class="fs-7">' + aman(p.mulai) + '</td>' +
                    '<td class="fs-7">' + aman(p.kumpul) + '</td>' +
                    '<td class="fs-7">' + (p.status === 'kerja' ? jam(p.sisa_detik) : '-') + '</td>' +
                    '<td class="fs-8 text-muted">' + p.keluar + ' / ' + p.kunci + '</td>' +
                    '<td class="fw-bold">' + (p.nilai === null ? '-' : p.nilai) + '</td>' +
                    '<td>' + (p.ttd ? '<a href="' + p.ttd + '" target="_blank" class="btn btn-sm btn-icon btn-light-primary"><i class="ki-outline ki-eye fs-5"></i></a>' : '-') + '</td>' +
                    '</tr>';
            }).join('') || '<tr><td colspan="9" class="text-center text-muted py-6">Belum ada peserta terdaftar pada sesi ini.</td></tr>';
        }
        gambar(awal);

        // Polling sederhana tiap 5 detik. Dipakai daripada websocket supaya tidak
        // menambah service yang harus terus hidup di server.
        var jalan = false;
        setInterval(function () {
            if (!autoSegar.checked || jalan || document.hidden) return;
            jalan = true;
            fetch(URL_DATA, { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (d) { if (d) gambar(d); })
                .catch(function () {})
                .finally(function () { jalan = false; });
        }, 5000);
    })();
</script>
@endpush
