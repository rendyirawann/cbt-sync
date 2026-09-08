{{-- Rincian satu baris Log Activity.

     Untuk 'updated', spatie menyimpan dua kumpulan di properties: 'old' (sebelum)
     dan 'attributes' (sesudah). Di sini keduanya disandingkan dan baris yang
     BERBEDA ditandai — tanpa itu, guru/admin harus membandingkan dua blok JSON
     sendiri untuk tahu apa yang berubah.

     Nilai atribut sensitif tetap disamarkan walau sudah dikecualikan saat
     pencatatan: baris log LAMA (dibuat sebelum pengecualian itu ada) bisa masih
     memuatnya. --}}
@php
    $props = $data->properties ?? collect();
    $props = is_array($props) ? collect($props) : $props;

    $sesudah = collect($props->get('attributes', []));
    $sebelum = collect($props->get('old', []));

    // Kunci teknis yang tidak perlu ditampilkan sebagai "perubahan data".
    $abaikan = ['ip', 'agent', 'updated_at', 'created_at'];
    $rahasia = ['password', 'remember_token', 'exam_password', 'api_token',
                'two_factor_secret', 'two_factor_recovery_codes'];

    $kunci = $sesudah->keys()->merge($sebelum->keys())->unique()
        ->reject(fn ($k) => in_array($k, $abaikan, true))->sort()->values();

    $tampil = function ($v) use ($rahasia) {
        if (is_null($v)) return '<span class="text-muted fst-italic">(kosong)</span>';
        if (is_bool($v)) return $v ? 'true' : 'false';
        if (is_array($v)) return '<code class="fs-8">' . e(json_encode($v, JSON_UNESCAPED_UNICODE)) . '</code>';
        $s = (string) $v;
        return $s === '' ? '<span class="text-muted fst-italic">(kosong)</span>'
                         : e(mb_strimwidth($s, 0, 220, '…'));
    };

    $ip = $props->get('ip');
    $agent = collect($props->get('agent', []));
    $adaPerubahan = $sebelum->isNotEmpty();
    $jmlBeda = $kunci->filter(fn ($k) => ($sebelum[$k] ?? null) != ($sesudah[$k] ?? null))->count();
@endphp

<div class="modal-header">
    <h3 class="modal-title">Rincian Log Aktivitas</h3>
    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
        <i class="ki-outline ki-cross fs-1 text-dark"></i>
    </div>
</div>

<div class="modal-body px-8 py-6">
    <div class="row g-4 mb-6">
        @foreach([
            'Aksi' => ucfirst($data->description ?? '-'),
            'Jenis Data' => $data->log_name ?? '-',
            'Oleh' => $data->causer->name ?? 'Sistem',
            'Waktu' => \Carbon\Carbon::parse($data->created_at)->timezone('Asia/Jakarta')->translatedFormat('d F Y H:i:s'),
            'Data Terkait' => class_basename($data->subject_type ?? '-') . ($data->subject_id ? ' #' . \Illuminate\Support\Str::limit($data->subject_id, 8, '') : ''),
            'IP / Perangkat' => trim(($ip ?: '-') . ' · ' . ($agent['browser'] ?? '-') . ' / ' . ($agent['os'] ?? '-'), ' ·'),
        ] as $label => $nilai)
            <div class="col-md-4">
                <div class="text-muted fs-8 text-uppercase fw-bold">{{ $label }}</div>
                <div class="fw-semibold text-gray-900">{{ $nilai }}</div>
            </div>
        @endforeach
    </div>

    @if($kunci->isEmpty())
        <div class="alert alert-light-info fs-7 mb-0">
            Tidak ada rincian atribut pada log ini. Umumnya terjadi pada catatan
            login/logout, atau aktivitas yang memang tidak mengubah data.
        </div>
    @else
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="mb-0">{{ $adaPerubahan ? 'Perubahan Data' : 'Data yang Dicatat' }}</h5>
            @if($adaPerubahan)
                <span class="badge badge-light-warning">{{ $jmlBeda }} kolom berubah</span>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table table-row-bordered table-row-dashed align-middle fs-7 gy-3">
                <thead class="fs-8 text-muted text-uppercase">
                    <tr>
                        <th class="w-200px">Kolom</th>
                        @if($adaPerubahan)<th>Sebelum</th>@endif
                        <th>{{ $adaPerubahan ? 'Sesudah' : 'Nilai' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kunci as $k)
                        @php
                            $lama = $sebelum[$k] ?? null;
                            $baru = $sesudah[$k] ?? null;
                            $beda = $adaPerubahan && $lama != $baru;
                            $disamarkan = in_array($k, $rahasia, true);
                        @endphp
                        <tr @class(['bg-light-warning' => $beda])>
                            <td class="fw-bold text-gray-800">
                                @if($beda)<i class="ki-outline ki-arrow-right fs-7 text-warning me-1"></i>@endif
                                {{ $k }}
                            </td>
                            @if($adaPerubahan)
                                <td class="text-muted">
                                    {!! $disamarkan ? '<span class="text-muted fst-italic">(disamarkan)</span>' : $tampil($lama) !!}
                                </td>
                            @endif
                            <td class="{{ $beda ? 'fw-bold text-gray-900' : '' }}">
                                {!! $disamarkan ? '<span class="text-muted fst-italic">(disamarkan)</span>' : $tampil($baru) !!}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($adaPerubahan)
            <div class="text-muted fs-8 mt-2">
                Baris bertanda kuning adalah kolom yang nilainya berubah.
                Kolom teknis (waktu ubah, IP, perangkat) tidak ikut ditampilkan di tabel ini.
            </div>
        @endif
    @endif
</div>
