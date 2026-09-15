@extends('backend.layout.app')
@section('title', 'Monitoring Ujian')

@section('content')
@php
    // Subtitle bergantung peran, jadi dihitung dulu di sini: parameter 'catatan'
    // milik kop-halaman menerima string, bukan blok Blade.
    $guruSaja = auth()->user()->hasRole('Guru');
    $catatan = $guruSaja
        ? 'Sesi dari ujian yang Anda ampu.'
        : 'Seluruh sesi ujian di sekolah Anda.';
@endphp
@include('partials.kop-halaman', [
    'judul' => 'Monitoring Ujian',
    'catatan' => $catatan,
    'jejak' => [
        ['label' => 'Akademik'],
        ['label' => 'Ujian / CBT', 'route' => 'exams.index'],
        ['label' => 'Monitoring Ujian', 'route' => 'exam-monitor.index'],
    ],
])

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-fluid px-4 px-lg-6">
        <div class="card card-flush">
            <div class="card-header mt-5">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">Sesi Ujian</h3>
                    {{-- Kotak cari dibuat sendiri: dom DataTables bawaan Metronic
                         tidak memuat 'f'. --}}
                    <div class="d-flex align-items-center position-relative mt-3">
                        <i class="ki-outline ki-magnifier fs-4 position-absolute ms-4 text-gray-500"></i>
                        <input type="text" id="cariMonitor" autocomplete="off"
                               class="form-control form-control-sm form-control-solid w-100 w-md-300px ps-11"
                               placeholder="Cari ujian, jadwal, mapel, atau kelas">
                    </div>
                </div>
            </div>
            <div class="card-body py-4">
                <div class="table-responsive">
                    <table id="tabelMonitor" class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                <th class="text-center">Status</th>
                                <th>Ujian / Jadwal</th>
                                <th>Mata Pelajaran</th>
                                <th>Kelas</th>
                                <th>Waktu</th>
                                <th class="text-center">Sudah Masuk</th>
                                @unless($guruSaja)<th>Guru</th>@endunless
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 fw-semibold">
                            @forelse($sessions as $s)
                                @php
                                    $berjalan = $s->isWithinSchedule();
                                    $akanDatang = $s->isUpcoming();
                                    $ikut = $s->attempts->count();
                                    // Bobot untuk pengurutan kolom Status: yang sedang
                                    // berjalan harus tetap naik ke atas walau kolom lain
                                    // diurutkan pengguna.
                                    $bobot = $berjalan ? 2 : ($akanDatang ? 1 : 0);
                                    $mulai = \Carbon\Carbon::parse($s->starts_at);
                                    $selesai = \Carbon\Carbon::parse($s->ends_at);
                                @endphp
                                <tr>
                                    <td class="text-center" data-order="{{ $bobot }}">
                                        @if($berjalan)
                                            <span class="badge badge-success">berjalan</span>
                                        @elseif($akanDatang)
                                            <span class="badge badge-light-warning">akan datang</span>
                                        @else
                                            <span class="badge badge-light">selesai</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-bold text-gray-900">{{ $s->exam->title }}</span>
                                        <span class="text-muted d-block fs-7">{{ $s->name }}</span>
                                    </td>
                                    <td>{{ $s->exam->teachingAssignment->subject->name ?? '-' }}</td>
                                    <td>{{ $s->classRoom->name ?? 'peserta pilihan' }}</td>
                                    <td data-order="{{ $mulai->timestamp }}">
                                        {{ $mulai->translatedFormat('d M Y') }} – {{ $selesai->translatedFormat('d M Y') }}
                                        <span class="text-muted d-block fs-7">{{ $s->duration_minutes }} menit</span>
                                    </td>
                                    <td class="text-center" data-order="{{ $ikut }}">
                                        <span class="badge badge-light-primary">{{ $ikut }}</span>
                                    </td>
                                    @unless($guruSaja)
                                        <td>{{ $s->exam->teachingAssignment->teacher->user->name ?? '-' }}</td>
                                    @endunless
                                    <td class="text-end">
                                        <a href="{{ route('exam-monitor.show', $s->id) }}" class="btn btn-sm btn-primary">
                                            <i class="ki-outline ki-screen fs-5 me-1"></i>Pantau
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="{{ $guruSaja ? 7 : 8 }}" class="text-center py-10 text-muted baris-kosong">
                                    Belum ada sesi ujian yang bisa dipantau.
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
{{-- DataTables tidak ikut di plugins.bundle.js, jadi dimuat sendiri di sini. --}}
<script src="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
<script>
    $(function () {
        // Tabel kosong = satu sel ber-colspan; DataTables menuntut sel sebanyak
        // kolom di kepala tabel dan akan melempar "Requested unknown parameter".
        if (document.querySelector('#tabelMonitor td.baris-kosong')) {
            return;
        }

        var guruSaja = @json($guruSaja);
        var kolomAksi = guruSaja ? 6 : 7;

        var tabel = $('#tabelMonitor').DataTable({
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']],
            // Urutan dari server sudah benar: yang sedang berjalan di atas, lalu
            // jadwal terbaru. Dibiarkan apa adanya supaya tidak berubah sendiri.
            order: [],
            columnDefs: [
                { orderable: false, searchable: false, targets: [kolomAksi] },
                { searchable: false, targets: [5] }
            ],
            language: {
                lengthMenu: 'Tampilkan _MENU_ baris',
                info: 'Menampilkan _START_–_END_ dari _TOTAL_ sesi',
                infoEmpty: 'Tidak ada sesi',
                infoFiltered: '(disaring dari _MAX_ total)',
                zeroRecords: 'Tidak ada sesi yang cocok dengan pencarian',
                emptyTable: 'Belum ada sesi ujian',
                paginate: { first: 'Awal', last: 'Akhir', next: 'Berikutnya', previous: 'Sebelumnya' }
            }
        });

        var kotakCari = document.getElementById('cariMonitor');
        if (kotakCari) {
            var jeda = null;
            kotakCari.addEventListener('keyup', function () {
                var nilai = this.value;
                clearTimeout(jeda);
                jeda = setTimeout(function () { tabel.search(nilai).draw(); }, 250);
            });
            kotakCari.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); }
            });
        }
    });
</script>
@endpush
@endsection
