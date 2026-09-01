@extends('backend.layout.app')

@section('content')
<div class="content d-flex flex-column flex-column-fluid" id="kt_content">
    <div class="toolbar" id="kt_toolbar">
        <div id="kt_toolbar_container" class="container-fluid d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="d-flex text-dark fw-bold fs-3 align-items-center my-1">Jadwal Pelajaran</h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-1">
                    <li class="breadcrumb-item text-muted">Akademik</li>
                    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
                    <li class="breadcrumb-item text-dark">Manajemen Jadwal</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="ki-outline ki-plus fs-2"></i> Tambah Jadwal
                </button>
            </div>
        </div>
    </div>

    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-xxl">
            <div class="card card-flush">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <h2>Daftar Jadwal Pelajaran</h2>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                    <th>Hari</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Guru</th>
                                    <th>Kelas</th>
                                    <th>Waktu</th>
                                    <th>Status</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="fw-semibold text-gray-600">
                                @forelse($schedules as $item)
                                <tr>
                                    <td>
                                        <span class="badge badge-light-primary fw-bold">{{ $item->day_name }}</span>
                                    </td>
                                    <td>{{ $item->teachingAssignment->subject->name }}</td>
                                    <td>{{ $item->teachingAssignment->teacher->user->name }}</td>
                                    <td>{{ $item->teachingAssignment->classRoom->name }}</td>
                                    <td>{{ date('H:i', strtotime($item->start_time)) }} - {{ date('H:i', strtotime($item->end_time)) }}</td>
                                    <td>
                                        @if($item->is_active)
                                            <span class="badge badge-light-success">Aktif</span>
                                        @else
                                            <span class="badge badge-light-danger">Non-Aktif</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#editModal{{ $item->id }}">
                                            <i class="ki-outline ki-pencil fs-2"></i>
                                        </button>
                                        <form action="{{ route('schedules.destroy', $item->id) }}" method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-icon btn-bg-light btn-active-color-danger btn-sm confirm-delete">
                                                <i class="ki-outline ki-trash fs-2"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="text-center px-4 py-15">
                                            <img src="{{ asset('assets/media/illustrations/sigma-1/5.png') }}" alt="" class="mw-100 mh-200px mb-7">
                                            <h3 class="fw-bold text-gray-900 mb-2">Belum ada jadwal pelajaran</h3>
                                            <p class="text-gray-400 fs-6 fw-semibold">Silakan tambahkan jadwal baru untuk menghubungkan mata pelajaran dengan hari dan waktu.</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade drawer-modal" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog mw-650px">
        <div class="modal-content">
            <form action="{{ route('schedules.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h2 class="fw-bold">Tambah Jadwal Baru</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body py-10 px-lg-17">
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Penugasan Mengajar (Mata Pelajaran & Kelas)</label>
                        <select name="teaching_assignment_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#addModal" required>
                            <option value="">Pilih Penugasan...</option>
                            @foreach($assignments as $a)
                                <option value="{{ $a->id }}">{{ $a->classRoom->name }} - {{ $a->subject->name }} ({{ $a->teacher->user->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Hari</label>
                        <select name="day_of_week" class="form-select form-select-solid" required>
                            <option value="1">Senin</option>
                            <option value="2">Selasa</option>
                            <option value="3">Rabu</option>
                            <option value="4">Kamis</option>
                            <option value="5">Jumat</option>
                            <option value="6">Sabtu</option>
                            <option value="7">Minggu</option>
                        </select>
                    </div>
                    <div class="row g-9 mb-7">
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">Jam Mulai</label>
                            <input type="time" name="start_time" class="form-control form-control-solid" required>
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">Jam Selesai</label>
                            <input type="time" name="end_time" class="form-control form-control-solid" required>
                        </div>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="fs-6 fw-semibold mb-2">Link Meeting (Zoom/Jitsi)</label>
                        <input type="url" name="meeting_url" class="form-control form-control-solid" placeholder="https://zoom.us/j/...">
                    </div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="submit" class="btn btn-primary">Simpan Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($schedules as $item)
<div class="modal fade drawer-modal" id="editModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog mw-650px">
        <div class="modal-content">
            <form action="{{ route('schedules.update', $item->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h2 class="fw-bold">Edit Jadwal</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body py-10 px-lg-17">
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Hari</label>
                        <select name="day_of_week" class="form-select form-select-solid" required>
                            <option value="1" {{ $item->day_of_week == 1 ? 'selected' : '' }}>Senin</option>
                            <option value="2" {{ $item->day_of_week == 2 ? 'selected' : '' }}>Selasa</option>
                            <option value="3" {{ $item->day_of_week == 3 ? 'selected' : '' }}>Rabu</option>
                            <option value="4" {{ $item->day_of_week == 4 ? 'selected' : '' }}>Kamis</option>
                            <option value="5" {{ $item->day_of_week == 5 ? 'selected' : '' }}>Jumat</option>
                            <option value="6" {{ $item->day_of_week == 6 ? 'selected' : '' }}>Sabtu</option>
                            <option value="7" {{ $item->day_of_week == 7 ? 'selected' : '' }}>Minggu</option>
                        </select>
                    </div>
                    <div class="row g-9 mb-7">
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">Jam Mulai</label>
                            <input type="time" name="start_time" class="form-control form-control-solid" value="{{ $item->start_time }}" required>
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">Jam Selesai</label>
                            <input type="time" name="end_time" class="form-control form-control-solid" value="{{ $item->end_time }}" required>
                        </div>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="fs-6 fw-semibold mb-2">Link Meeting (Zoom/Jitsi)</label>
                        <input type="url" name="meeting_url" class="form-control form-control-solid" value="{{ $item->meeting_url }}" placeholder="https://zoom.us/j/...">
                    </div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="submit" class="btn btn-primary">Perbarui Jadwal</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@push('scripts')
<script>
    document.querySelectorAll('.confirm-delete').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data jadwal ini akan dihapus secara permanen!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endpush

@endsection
