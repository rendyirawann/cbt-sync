{{-- Ringkasan CBT di Dashboard pengelola. Seluruh angka dari database
     (App\Services\RingkasanCbt), dibatasi ke sekolah pengguna, dan untuk Guru
     dibatasi lagi ke ujian yang ia ampu. --}}
@php
    $r = $cbt ?? null;
    $n = fn ($v) => number_format((float) $v, 0, ',', '.');
    $nilai = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
@endphp
@if($r)
<div class="row g-5 g-xl-8 mb-5">
    @foreach([
        ['Ujian', $r['ujian_total'], 'ki-book-open', 'primary', 'seluruh ujian yang terlihat oleh Anda'],
        ['Sedang Mengerjakan', $r['sedang_mengerjakan'], 'ki-timer', 'warning', 'siswa yang ujiannya berjalan sekarang'],
        ['Menunggu Dinilai', $r['menunggu_dinilai'], 'ki-notepad-edit', 'danger', 'sudah dikumpulkan, belum dinilai'],
        ['Sudah Dinilai', $r['sudah_dinilai'], 'ki-verify', 'success', 'nilai akhirnya sudah keluar'],
    ] as [$judul, $angka, $ikon, $warna, $ket])
        <div class="col-sm-6 col-xl-3">
            <div class="card card-flush h-100">
                <div class="card-body d-flex flex-column justify-content-between py-5">
                    <div class="d-flex align-items-center mb-2">
                        <span class="symbol symbol-40px me-3">
                            <span class="symbol-label bg-light-{{ $warna }}">
                                <i class="ki-outline {{ $ikon }} fs-2 text-{{ $warna }}"></i>
                            </span>
                        </span>
                        <div>
                            <div class="fs-2hx fw-bold text-gray-900 lh-1">{{ $n($angka) }}</div>
                            <div class="fs-7 fw-semibold text-gray-600">{{ $judul }}</div>
                        </div>
                    </div>
                    <div class="text-muted fs-8">{{ $ket }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-5 g-xl-8 mb-5">
    <div class="col-xl-4">
        <div class="card card-flush h-100">
            <div class="card-header pt-5">
                <h3 class="card-title fs-5 fw-bold">Status Ujian</h3>
            </div>
            <div class="card-body pt-3">
                @foreach($r['ujian_per_status'] as $status => $jml)
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge badge-light-{{ \App\Support\SiklusUjian::warnaStatus($status) }}">
                            {{ \App\Support\SiklusUjian::labelStatus($status) }}
                        </span>
                        <span class="fw-bold text-gray-800">{{ $n($jml) }}</span>
                    </div>
                @endforeach
                <div class="separator my-4"></div>
                @foreach([
                    'Jadwal berjalan' => $r['jadwal_berjalan'],
                    'Jadwal akan datang' => $r['jadwal_akan_datang'],
                    'Essay menunggu koreksi' => $r['essay_menunggu'],
                ] as $label => $jml)
                    <div class="d-flex align-items-center justify-content-between mb-2 fs-7">
                        <span class="text-muted">{{ $label }}</span>
                        <span class="fw-bold text-gray-800">{{ $n($jml) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card card-flush h-100">
            <div class="card-header pt-5">
                <h3 class="card-title fs-5 fw-bold">Nilai</h3>
                <div class="card-toolbar"><span class="text-muted fs-8">dari {{ $n($r['nilai_jumlah']) }} pengerjaan dinilai</span></div>
            </div>
            <div class="card-body pt-3">
                <div class="d-flex align-items-end mb-4">
                    <span class="fs-2hx fw-bold text-gray-900 lh-1">{{ $nilai($r['nilai_rata']) }}</span>
                    <span class="text-muted fs-7 ms-2 mb-1">rata-rata</span>
                </div>
                <div class="d-flex justify-content-between fs-7 mb-2">
                    <span class="text-muted">Terendah</span><span class="fw-bold">{{ $nilai($r['nilai_terendah']) }}</span>
                </div>
                <div class="d-flex justify-content-between fs-7 mb-4">
                    <span class="text-muted">Tertinggi</span><span class="fw-bold">{{ $nilai($r['nilai_tertinggi']) }}</span>
                </div>
                @php $tot = $r['lulus'] + $r['tidak_lulus']; $pct = $tot ? round($r['lulus'] / $tot * 100) : 0; @endphp
                {{-- Lulus dihitung terhadap KKM masing-masing ujian, bukan satu angka tetap. --}}
                <div class="d-flex justify-content-between fs-7 mb-1">
                    <span class="text-muted">Lulus KKM</span>
                    <span class="fw-bold text-success">{{ $n($r['lulus']) }} / {{ $n($tot) }} ({{ $pct }}%)</span>
                </div>
                <div class="progress h-6px">
                    <div class="progress-bar bg-success" style="width: {{ $pct }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card card-flush h-100">
            <div class="card-header pt-5">
                <h3 class="card-title fs-5 fw-bold">Bahan & Peserta</h3>
            </div>
            <div class="card-body pt-3">
                @foreach([
                    'Soal Pilihan Ganda' => $r['soal_pg'],
                    'Soal Essay' => $r['soal_essay'],
                    'Soal di Bank Soal' => $r['bank_soal'],
                    'Siswa terdaftar' => $r['siswa'],
                ] as $label => $jml)
                    <div class="d-flex align-items-center justify-content-between mb-3 fs-7">
                        <span class="text-muted">{{ $label }}</span>
                        <span class="fw-bold text-gray-800">{{ $n($jml) }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@if($r['berjalan']->isNotEmpty())
<div class="card card-flush mb-5">
    <div class="card-header pt-5">
        <h3 class="card-title fs-5 fw-bold">Ujian Sedang Berjalan</h3>
        <div class="card-toolbar"><a href="{{ route('exam-monitor.index') }}" class="btn btn-sm btn-light-primary">Pantau</a></div>
    </div>
    <div class="card-body pt-3">
        <div class="table-responsive">
            <table class="table table-row-dashed align-middle fs-7 gy-3 mb-0">
                <thead class="fs-8 text-muted text-uppercase">
                    <tr><th>Ujian</th><th>Mapel / Kelas</th><th class="text-center">Peserta</th>
                        <th class="text-center">Berjalan</th><th class="text-center">Selesai</th><th>Berakhir</th></tr>
                </thead>
                <tbody>
                    @foreach($r['berjalan'] as $s)
                        <tr>
                            <td class="fw-bold text-gray-900">{{ $s->exam->title ?? '-' }}</td>
                            <td class="text-muted">{{ $s->exam->teachingAssignment->subject->name ?? '-' }}
                                <span class="text-gray-500">· {{ $s->exam->teachingAssignment->classRoom->name ?? '-' }}</span></td>
                            <td class="text-center fw-bold">{{ $n($s->jml_peserta) }}</td>
                            <td class="text-center"><span class="badge badge-light-warning">{{ $n($s->jml_berjalan) }}</span></td>
                            <td class="text-center"><span class="badge badge-light-success">{{ $n($s->jml_selesai) }}</span></td>
                            <td class="text-muted">{{ \Carbon\Carbon::parse($s->ends_at)->translatedFormat('d M Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endif
