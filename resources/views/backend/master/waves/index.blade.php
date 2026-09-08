@extends('backend.layout.app')
@section('title', 'Master Gelombang')
@section('content')
@include('partials.kop-halaman', [
    'judul' => 'Master Gelombang',
    'jejak' => [
        ['label' => 'Data Master'],
        ['label' => 'Master Gelombang', 'route' => 'waves.index'],
    ],
])
<div class="app-content flex-column-fluid">
    <div class="app-container container-fluid px-4 px-lg-6">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        <div class="card card-flush mt-6 mt-xl-9">
            <div class="card-header mt-5">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">Master Gelombang</h3>
                    <div class="fs-6 text-gray-500">Giliran waktu pelaksanaan ujian yang memakai ruang komputer yang sama</div>
                </div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Gelombang</button>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle fw-bold">
                        <thead class="fs-7 text-gray-400 text-uppercase">
                            <tr>
                                <th style="width:90px">Urutan</th>
                                <th>Nama Gelombang</th>
                                <th>Jam (PUKUL)</th>
                                <th class="text-center">Dipakai</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="fs-6">
                            @forelse($waves as $item)
                            <tr>
                                <td class="text-muted">{{ $item->sort_order }}</td>
                                <td>{{ $item->name }}</td>
                                <td>
                                    @if($item->rentang_jam)
                                        {{ $item->rentang_jam }}
                                    @else
                                        <span class="text-muted fw-semibold">belum diisi</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($item->students_count)
                                        <span class="badge badge-light-primary">{{ $item->students_count }} siswa</span>
                                    @else
                                        <span class="text-muted fw-semibold">belum dipakai</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-light-{{ $item->is_active ? 'success' : 'secondary' }}">
                                        {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light-primary btn-active-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $item->id }}">Edit</button>
                                    <form action="{{ route('waves.destroy', $item->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger btn-active-danger confirm-delete">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6">
                                    <div class="text-center px-4 py-15">
                                        <h3 class="fw-bold text-gray-900 mb-2">Belum ada gelombang</h3>
                                        <p class="text-gray-400 fs-6 fw-semibold">Tambahkan gelombang untuk membagi peserta ke beberapa giliran waktu.</p>
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

<!-- Add Modal -->
<div class="modal fade drawer-modal" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <form action="{{ route('waves.store') }}" method="POST">
            @csrf
            <div class="modal-header"><h2 class="fw-bold">Tambah Gelombang</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1 text-dark"></i></div>
            </div>
            <div class="modal-body">
                <div class="fv-row mb-7">
                    <label class="required fs-6 fw-semibold mb-2">Nama Gelombang</label>
                    <input type="text" name="name" class="form-control form-control-solid" placeholder="cth: Gelombang 3" required>
                </div>
                <div class="fv-row mb-7">
                    <label class="fs-6 fw-semibold mb-2">Urutan</label>
                    <input type="number" name="sort_order" class="form-control form-control-solid" value="{{ ($waves->max('sort_order') ?? 0) + 1 }}" min="0" max="999">
                    <div class="text-muted fs-7 mt-2">Menentukan urutan tampil pada pilihan gelombang di data siswa.</div>
                </div>
                <div class="row mb-7">
                    <div class="col-md-6">
                        <label class="fs-6 fw-semibold mb-2">Jam Mulai</label>
                        <input type="time" name="start_time" class="form-control form-control-solid" value="07:30">
                    </div>
                    <div class="col-md-6">
                        <label class="fs-6 fw-semibold mb-2">Jam Selesai</label>
                        <input type="time" name="end_time" class="form-control form-control-solid" value="09:40">
                    </div>
                </div>
                <div class="text-muted fs-7 mb-7">Jam ini yang tercetak pada kolom <b>PUKUL</b> di lembar Daftar Hadir Peserta.
                    Jadwal ujian sendiri hanya berupa rentang tanggal, jadi jam pelaksanaan diatur di sini.</div>
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" checked>
                    <span class="ms-2 fw-semibold">Aktif (muncul di pilihan gelombang)</span>
                </label>
            </div>
            <div class="modal-footer flex-center">
                <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div></div>
</div>

@foreach($waves as $item)
<!-- Edit Modal -->
<div class="modal fade drawer-modal" id="editModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <form action="{{ route('waves.update', $item->id) }}" method="POST">
            @csrf @method('PUT')
            <div class="modal-header"><h2 class="fw-bold">Edit Gelombang</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1 text-dark"></i></div>
            </div>
            <div class="modal-body">
                <div class="fv-row mb-7">
                    <label class="required fs-6 fw-semibold mb-2">Nama Gelombang</label>
                    <input type="text" name="name" class="form-control form-control-solid" value="{{ $item->name }}" required>
                </div>
                <div class="fv-row mb-7">
                    <label class="fs-6 fw-semibold mb-2">Urutan</label>
                    <input type="number" name="sort_order" class="form-control form-control-solid" value="{{ $item->sort_order }}" min="0" max="999">
                </div>
                <div class="row mb-7">
                    <div class="col-md-6">
                        <label class="fs-6 fw-semibold mb-2">Jam Mulai</label>
                        <input type="time" name="start_time" class="form-control form-control-solid" value="{{ $item->start_time ? \Carbon\Carbon::parse($item->start_time)->format('H:i') : '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="fs-6 fw-semibold mb-2">Jam Selesai</label>
                        <input type="time" name="end_time" class="form-control form-control-solid" value="{{ $item->end_time ? \Carbon\Carbon::parse($item->end_time)->format('H:i') : '' }}">
                    </div>
                </div>
                <div class="text-muted fs-7 mb-7">Jam ini yang tercetak pada kolom <b>PUKUL</b> di lembar Daftar Hadir Peserta.
                    Jadwal ujian sendiri hanya berupa rentang tanggal, jadi jam pelaksanaan diatur di sini.</div>
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" @checked($item->is_active)>
                    <span class="ms-2 fw-semibold">Aktif (muncul di pilihan gelombang)</span>
                </label>
            </div>
            <div class="modal-footer flex-center">
                <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div></div>
</div>
@endforeach

@push('scripts')
<script>
    document.querySelectorAll('.confirm-delete').forEach(function (b) {
        b.addEventListener('click', function (e) {
            e.preventDefault();
            var form = this.closest('form');
            Swal.fire({
                title: 'Hapus gelombang ini?', text: 'Gelombang yang masih dipakai siswa tidak akan dihapus.',
                icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33',
                confirmButtonText: 'Ya, hapus', cancelButtonText: 'Batal'
            }).then(function (r) { if (r.isConfirmed) form.submit(); });
        });
    });
</script>
@endpush
@endsection
