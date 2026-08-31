@extends('frontend.layout.app')
@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="toolbar" id="kt_toolbar">
        <div id="kt_toolbar_container" class="container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="d-flex text-dark fw-bold fs-3 align-items-center my-1">Portal Absensi Mandiri</h1>
                <p class="text-muted fs-7 fw-semibold my-1">Silakan lakukan absensi sesuai jadwal yang berlaku.</p>
            </div>
        </div>
    </div>

    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-xxl">
            <!-- Daily Attendance Cards -->
            <div class="row g-5 g-xl-10 mb-10">
                <!-- Arrival -->
                <div class="col-md-6 col-xl-4">
                    <div class="card card-flush h-md-100 border-0 shadow-sm" style="background-color: #F1FAFF">
                        <div class="card-header pt-5">
                            <div class="card-title d-flex flex-column">
                                <span class="fs-2hx fw-bold text-dark me-2 lh-1 ls-n2">{{ date('H:i', strtotime($settings->arrival_start)) }} - {{ date('H:i', strtotime($settings->arrival_end)) }}</span>
                                <span class="text-gray-400 pt-1 fw-semibold fs-6">Jadwal Absen Datang</span>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column justify-content-end pr-0">
                            @if($arrival)
                                <div class="d-flex flex-column mb-2">
                                    <span class="badge badge-success fs-7 fw-bold mb-1">TERABSENSI: {{ $arrival->attended_at->format('H:i') }}</span>
                                    @if($arrival->status == 'terlambat')
                                        <span class="badge badge-warning fs-8">TERLAMBAT {{ $arrival->late_minutes }} MENIT</span>
                                    @endif
                                </div>
                            @else
                                @if($canAttendArrival)
                                    <button class="btn btn-primary w-100 btn-absensi" data-type="datang">
                                        <i class="ki-outline ki-entrance-left fs-2"></i> {{ $isArrivalLate ? 'Absen Datang (Terlambat)' : 'Absen Datang Sekarang' }}
                                    </button>
                                @else
                                    <button class="btn btn-secondary w-100" disabled>
                                        <i class="ki-outline ki-lock fs-2"></i> Waktu Absen Berakhir
                                    </button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Departure -->
                <div class="col-md-6 col-xl-4">
                    <div class="card card-flush h-md-100 border-0 shadow-sm" style="background-color: #FFF5F8">
                        <div class="card-header pt-5">
                            <div class="card-title d-flex flex-column">
                                <span class="fs-2hx fw-bold text-dark me-2 lh-1 ls-n2">Mulai {{ date('H:i', strtotime($settings->departure_start)) }}</span>
                                <span class="text-gray-400 pt-1 fw-semibold fs-6">Jadwal Absen Pulang</span>
                            </div>
                        </div>
                        <div class="card-body d-flex flex-column justify-content-end pr-0">
                            @if($departure)
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge badge-danger fs-7 fw-bold">PULANG PADA: {{ $departure->attended_at->format('H:i') }}</span>
                                </div>
                            @else
                                <button class="btn btn-danger w-100 btn-absensi" data-type="pulang" {{ !$arrival ? 'disabled' : '' }}>
                                    <i class="ki-outline ki-entrance-right fs-2"></i> Absen Pulang Sekarang
                                </button>
                                @if(!$arrival)
                                    <span class="text-muted fs-8 mt-2">Selesaikan absen datang terlebih dahulu</span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Clock -->
                <div class="col-md-12 col-xl-4">
                    <div class="card card-flush h-md-100 border-0 shadow-sm bg-primary">
                        <div class="card-body d-flex flex-column flex-center text-white">
                            <h2 class="text-white opacity-75 mb-2">Waktu Saat Ini</h2>
                            <h1 class="text-white fs-4x fw-bold mb-2" id="realtime-clock">{{ date('H:i:s') }}</h1>
                            <p class="fs-6 fw-semibold text-white opacity-75">{{ date('l, d F Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subject Attendance Section -->
            <div class="card card-flush">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2>Jadwal Mata Pelajaran Hari Ini</h2>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Waktu Pelajaran</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Guru Pengampu</th>
                                    <th class="text-end">Aksi Absensi</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-600">
                                @forelse($schedules as $s)
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-dark fw-bold">{{ date('H:i', strtotime($s->start_time)) }} - {{ date('H:i', strtotime($s->end_time)) }}</span>
                                            <span class="fs-8 text-muted">Durasi: {{ Carbon\Carbon::parse($s->start_time)->diffInMinutes(Carbon\Carbon::parse($s->end_time)) }} Menit</span>
                                        </div>
                                    </td>
                                    <td>{{ $s->teachingAssignment->subject->name }}</td>
                                    <td>{{ $s->teachingAssignment->teacher->user->name }}</td>
                                    <td class="text-end">
                                        @if($s->is_attended)
                                            <div class="d-flex flex-column align-items-end">
                                                <span class="badge badge-light-success px-4 py-3">Sudah Absen</span>
                                                @if($s->is_attended->status == 'terlambat')
                                                    <span class="fs-8 text-warning fw-bold mt-1">Terlambat {{ $s->is_attended->late_minutes }}m</span>
                                                @endif
                                            </div>
                                        @elseif($s->can_attend)
                                            <button class="btn btn-sm {{ $s->is_late_submission ? 'btn-light-warning' : 'btn-light-primary' }} btn-absensi" data-type="mapel" data-id="{{ $s->id }}">
                                                <i class="ki-outline ki-check-circle fs-2"></i> {{ $s->is_late_submission ? 'Absen (Terlambat)' : 'Klik Untuk Absen' }}
                                            </button>
                                        @elseif($s->is_late)
                                            <span class="badge badge-light-danger px-4 py-3">Waktu Terlewat</span>
                                        @else
                                            <span class="badge badge-light-secondary px-4 py-3">Belum Mulai</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-10">
                                        <img src="{{ asset('assets/media/illustrations/sigma-1/5.png') }}" class="h-100px mb-5" alt="">
                                        <div class="fw-bold fs-3 text-muted">Tidak ada jadwal pelajaran untuk hari ini.</div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- History Attendance Section -->
            <div class="card card-flush mt-10">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2>Riwayat Absensi Saya</h2>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_attendance_history_table">
                            <thead>
                                <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Tanggal</th>
                                    <th>Tipe Absensi</th>
                                    <th>Status</th>
                                    <th>Waktu Absen</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-600">
                                @foreach($history as $h)
                                <tr>
                                    <td>{{ $h->created_at->format('d M Y') }}</td>
                                    <td>
                                        @if($h->type == 'datang')
                                            <span class="badge badge-light-primary">Datang</span>
                                        @elseif($h->type == 'pulang')
                                            <span class="badge badge-light-danger">Pulang</span>
                                        @else
                                            <span class="badge badge-light-info">Pelajaran ({{ $h->schedule->teachingAssignment->subject->name ?? '-' }})</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($h->status == 'hadir')
                                            <span class="badge badge-success">Hadir</span>
                                        @elseif($h->status == 'terlambat')
                                            <span class="badge badge-warning">Terlambat</span>
                                        @elseif($h->status == 'izin')
                                            <span class="badge badge-primary">Izin</span>
                                        @elseif($h->status == 'sakit')
                                            <span class="badge badge-info">Sakit</span>
                                        @else
                                            <span class="badge badge-danger">Alpha</span>
                                        @endif
                                    </td>
                                    <td>{{ Carbon\Carbon::parse($h->attended_at)->format('H:i') }}</td>
                                    <td>
                                        @if($h->status == 'terlambat')
                                            Terlambat {{ $h->late_minutes }} menit
                                        @else
                                            Tepat Waktu
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // DataTable Initialization
    $('#kt_attendance_history_table').DataTable({
        "info": false,
        'order': [],
        'pageLength': 10,
    });

    // Realtime Clock
    setInterval(() => {
        const now = new Date();
        const time = now.toLocaleTimeString('id-ID', { hour12: false });
        document.getElementById('realtime-clock').innerText = time;
    }, 1000);

    // Ajax Attendance Submit
    $('.btn-absensi').on('click', function() {
        const type = $(this).data('type');
        const id = $(this).data('id');
        const btn = $(this);

        Swal.fire({
            title: 'Konfirmasi Absensi',
            text: `Apakah Anda yakin ingin melakukan absensi ${type}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Absen Sekarang!',
            cancelButtonText: 'Batal',
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-light'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
                
                $.ajax({
                    url: "{{ route('student.attendance.submit') }}",
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        type: type,
                        schedule_id: id
                    },
                    success: function(res) {
                        Swal.fire('Berhasil!', res.message, 'success').then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false).html(btn.data('type') == 'mapel' ? 'Klik Untuk Absen' : 'Absen Sekarang');
                        Swal.fire('Gagal!', xhr.responseJSON.message, 'error');
                    }
                });
            }
        });
    });
</script>
@endpush
@endsection
