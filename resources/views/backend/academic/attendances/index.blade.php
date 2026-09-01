@extends('backend.layout.app')

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="toolbar" id="kt_toolbar">
        <div id="kt_toolbar_container" class="container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="d-flex text-dark fw-bold fs-3 align-items-center my-1">Log Absensi & Pengaturan</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-1">
                    <li class="breadcrumb-item text-muted">Akademik</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-dark">Absensi</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-xxl">
            <!-- Attendance Settings -->
            <div class="card card-flush mb-10">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2>Pengaturan Waktu Absensi</h2>
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('attendance-settings.update') }}" method="POST" class="row g-9">
                        @csrf
                        <div class="col-md-4 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">Batas Awal Absen Datang</label>
                            <input type="time" name="arrival_start" class="form-control form-control-solid" value="{{ $settings->arrival_start }}" required>
                        </div>
                        <div class="col-md-4 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">Batas Akhir Absen Datang</label>
                            <input type="time" name="arrival_end" class="form-control form-control-solid" value="{{ $settings->arrival_end }}" required>
                        </div>
                        <div class="col-md-4 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">Waktu Absen Pulang (Mulai)</label>
                            <input type="time" name="departure_start" class="form-control form-control-solid" value="{{ $settings->departure_start }}" required>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Attendance Log -->
            <div class="card card-flush">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2>Riwayat Absensi Keseluruhan</h2>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Waktu</th>
                                    <th>Nama Siswa</th>
                                    <th>Tipe</th>
                                    <th>Detail</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-600">
                                @forelse($attendances as $item)
                                <tr>
                                    <td>{{ $item->attended_at->format('d M Y, H:i') }}</td>
                                    <td>{{ $item->user->name }}</td>
                                    <td>
                                        @if($item->type == 'datang')
                                            <span class="badge badge-light-primary">Datang</span>
                                        @elseif($item->type == 'pulang')
                                            <span class="badge badge-light-info">Pulang</span>
                                        @else
                                            <span class="badge badge-light-warning">Mata Pelajaran</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->type == 'mapel')
                                            {{ $item->schedule->teachingAssignment->subject->name ?? '-' }}
                                        @else
                                            Absensi Harian
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $item->status == 'hadir' ? 'success' : ($item->status == 'terlambat' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($item->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="text-center px-4 py-15">
                                            <img src="{{ asset('assets/media/illustrations/sigma-1/5.png') }}" alt="" class="mw-100 mh-200px mb-7">
                                            <h3 class="fw-bold text-gray-900 mb-2">Belum ada log absensi hari ini</h3>
                                            <p class="text-gray-400 fs-6 fw-semibold">Data kehadiran siswa (Datang, Pulang, Mapel) akan muncul di sini setelah siswa melakukan absensi.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-5">
                        {{ $attendances->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
