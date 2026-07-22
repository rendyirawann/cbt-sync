@extends('backend.layout.app')
@section('title', 'Sekolah')
@section('content')
<div class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        <div class="card card-flush mt-6 mt-xl-9">
            <div class="card-header mt-5">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">Manajemen Sekolah</h3>
                </div>
                <div class="card-toolbar">
                    @include('backend.master._import_tools', ['templateRoute' => 'schools.template', 'importRoute' => 'schools.import', 'label' => 'Sekolah'])
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Data</button>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle fw-bold">
                        <thead class="fs-7 text-gray-400 text-uppercase">
                            <tr>
                                <th>Nama</th>
                                <th>Alamat</th>
                                <th>Telepon</th>
                                <th>Email</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="fs-6">
                            @forelse($schools as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->address }}</td>
                                <td>{{ $item->phone }}</td>
                                <td>{{ $item->email }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light-primary btn-active-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $item->id }}">Edit</button>
                                    <form action="{{ route('schools.destroy', $item->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger btn-active-danger confirm-delete" >Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5">
                                    <div class="text-center px-4 py-15">
                                        <img src="{{ asset('assets/media/illustrations/sigma-1/5.png') }}" alt="" class="mw-100 mh-200px mb-7">
                                        <h3 class="fw-bold text-gray-900 mb-2">Belum ada data sekolah</h3>
                                        <p class="text-gray-400 fs-6 fw-semibold">Silakan tambahkan profil sekolah Anda untuk memulai konfigurasi LMS.</p>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('schools.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h2 class="fw-bold">Tambah Sekolah</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1 text-dark"></i>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="fv-row mb-7"><label class="required fs-6 fw-semibold mb-2">Nama</label><input type="text" name="name" class="form-control form-control-solid" required></div>
                    <div class="fv-row mb-7"><label class="required fs-6 fw-semibold mb-2">Alamat</label><input type="text" name="address" class="form-control form-control-solid" required></div>
                    <div class="fv-row mb-7"><label class="required fs-6 fw-semibold mb-2">Telepon</label><input type="text" name="phone" class="form-control form-control-solid" required></div>
                    <div class="fv-row mb-7"><label class="required fs-6 fw-semibold mb-2">Email</label><input type="text" name="email" class="form-control form-control-solid" required></div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($schools as $item)
<!-- Edit Modal -->
<div class="modal fade drawer-modal" id="editModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('schools.update', $item->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header"><h2 class="fw-bold">Edit Sekolah</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1 text-dark"></i>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="fv-row mb-7"><label class="required fs-6 fw-semibold mb-2">Nama</label><input type="text" name="name" class="form-control form-control-solid" value="{{ $item->name }}" required></div>
                    <div class="fv-row mb-7"><label class="required fs-6 fw-semibold mb-2">Alamat</label><input type="text" name="address" class="form-control form-control-solid" value="{{ $item->address }}" required></div>
                    <div class="fv-row mb-7"><label class="required fs-6 fw-semibold mb-2">Telepon</label><input type="text" name="phone" class="form-control form-control-solid" value="{{ $item->phone }}" required></div>
                    <div class="fv-row mb-7"><label class="required fs-6 fw-semibold mb-2">Email</label><input type="text" name="email" class="form-control form-control-solid" value="{{ $item->email }}" required></div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
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
                text: "Data yang dihapus tidak dapat dikembalikan!",
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