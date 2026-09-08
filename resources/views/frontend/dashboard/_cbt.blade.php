{{-- Ringkasan CBT di Dashboard siswa. Semua dari database; hanya menampilkan
     hal yang bisa ditindaklanjuti siswa: yang siap dikerjakan, yang sedang
     dikerjakan, jadwal berikutnya, dan nilai yang sudah keluar. --}}
@php
    $r = $cbt ?? null;
    $nilai = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
@endphp
@if($r)
{{-- Yang sedang dikerjakan didahulukan: itu yang paling mendesak bagi siswa. --}}
@if($r['sedang_dikerjakan']->isNotEmpty())
    @foreach($r['sedang_dikerjakan'] as $s)
        <div class="alert alert-warning d-flex align-items-center flex-wrap gap-3 mb-5">
            <i class="ki-outline ki-timer fs-2x text-warning"></i>
            <div class="flex-grow-1">
                <div class="fw-bold">Ujian belum selesai: {{ $s->exam->title ?? '-' }}</div>
                <div class="fs-7">{{ $s->exam->teachingAssignment->subject->name ?? '-' }} —
                    berakhir {{ \Carbon\Carbon::parse($s->ends_at)->translatedFormat('d M Y H:i') }}</div>
            </div>
            <a href="{{ route('student.exams.attempt', $s->id) }}" class="btn btn-sm btn-warning">Lanjutkan</a>
        </div>
    @endforeach
@endif

<div class="row g-5 mb-5">
    @foreach([
        ['Siap Dikerjakan', $r['siap_dikerjakan']->count(), 'ki-rocket', 'primary'],
        ['Menunggu Nilai', $r['menunggu_nilai'], 'ki-hourglass', 'warning'],
        ['Nilai Keluar', $r['sudah_dinilai'], 'ki-verify', 'success'],
        ['Rata-rata Nilai', $nilai($r['nilai_rata']), 'ki-chart-simple', 'info'],
    ] as [$judul, $angka, $ikon, $warna])
        <div class="col-6 col-xl-3">
            <div class="card card-flush h-100">
                <div class="card-body py-5 d-flex align-items-center">
                    <span class="symbol symbol-40px me-3">
                        <span class="symbol-label bg-light-{{ $warna }}">
                            <i class="ki-outline {{ $ikon }} fs-2 text-{{ $warna }}"></i>
                        </span>
                    </span>
                    <div>
                        <div class="fs-2hx fw-bold text-gray-900 lh-1">{{ $angka }}</div>
                        <div class="fs-8 fw-semibold text-gray-600">{{ $judul }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-5 mb-5">
    <div class="col-xl-7">
        <div class="card card-flush h-100">
            <div class="card-header pt-5">
                <h3 class="card-title fs-5 fw-bold">Ujian Siap Dikerjakan</h3>
            </div>
            <div class="card-body pt-3">
                @forelse($r['siap_dikerjakan'] as $s)
                    <div class="d-flex align-items-center flex-wrap gap-3 border border-gray-300 border-dashed rounded p-4 mb-3">
                        <div class="flex-grow-1">
                            <div class="fw-bold text-gray-900">{{ $s->exam->title ?? '-' }}</div>
                            <div class="text-muted fs-7">{{ $s->exam->teachingAssignment->subject->name ?? '-' }}
                                · sampai {{ \Carbon\Carbon::parse($s->ends_at)->translatedFormat('d M Y H:i') }}</div>
                        </div>
                        <a href="{{ route('student.exams.index') }}" class="btn btn-sm btn-light-primary">Buka</a>
                    </div>
                @empty
                    <div class="text-muted fs-7">Tidak ada ujian yang bisa dikerjakan sekarang.</div>
                @endforelse

                @if($r['akan_datang']->isNotEmpty())
                    <div class="separator my-4"></div>
                    <div class="fw-bold fs-7 text-gray-700 mb-2">Jadwal Berikutnya</div>
                    @foreach($r['akan_datang'] as $s)
                        <div class="d-flex justify-content-between fs-7 mb-2">
                            <span class="text-gray-800">{{ $s->exam->title ?? '-' }}</span>
                            <span class="text-muted">{{ \Carbon\Carbon::parse($s->starts_at)->translatedFormat('d M Y H:i') }}</span>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card card-flush h-100">
            <div class="card-header pt-5">
                <h3 class="card-title fs-5 fw-bold">Nilai Terakhir</h3>
            </div>
            <div class="card-body pt-3">
                @forelse($r['hasil_terakhir'] as $a)
                    @php
                        $kkm = (float) ($a->session->exam->pass_score ?? 0);
                        $lulus = (float) $a->final_score >= $kkm;
                    @endphp
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="me-3">
                            <div class="fw-semibold text-gray-900 fs-7">{{ $a->session->exam->title ?? '-' }}</div>
                            <div class="text-muted fs-8">{{ $a->session->exam->teachingAssignment->subject->name ?? '-' }}
                                · KKM {{ $nilai($kkm) }}</div>
                        </div>
                        <span class="badge badge-light-{{ $lulus ? 'success' : 'danger' }} fs-7 fw-bold">
                            {{ $nilai($a->final_score) }}
                        </span>
                    </div>
                @empty
                    <div class="text-muted fs-7">Belum ada nilai yang keluar.</div>
                @endforelse

                @if($r['gelombang'] || $r['ruang'] || $r['id_proktor'])
                    <div class="separator my-4"></div>
                    <div class="fw-bold fs-7 text-gray-700 mb-2">Data Ujian Kamu</div>
                    {{-- Ditampilkan di sini supaya siswa tidak perlu membuka Kartu Ujian
                         hanya untuk melihat ruang atau gelombangnya. --}}
                    @foreach(array_filter([
                        'Gelombang' => $r['gelombang'],
                        'Ruang' => $r['ruang'],
                        'ID Proktor' => $r['id_proktor'],
                    ]) as $label => $isi)
                        <div class="d-flex justify-content-between fs-7 mb-1">
                            <span class="text-muted">{{ $label }}</span>
                            <span class="fw-semibold text-gray-800">{{ $isi }}</span>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
@endif
