@extends('backend.layout.app')
@section('title', 'Tahun Ajaran')
@section('content')
<div class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        <div class="card card-flush mt-6 mt-xl-9">
            <div class="card-header mt-5">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">Manajemen Tahun Ajaran</h3>
                </div>
                <div class="card-toolbar">
                    @include('backend.master._import_tools', ['templateRoute' => 'academic-years.template', 'importRoute' => 'academic-years.import', 'label' => 'Tahun Ajaran'])
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Data</button>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle fw-bold">
                        <thead class="fs-7 text-gray-400 text-uppercase">
                            <tr>
                                <th>Tahun Ajaran</th>
                                <th>Semester</th>
                                <th>Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="fs-6">
                            @foreach($academicYears as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->semester }}</td>
                                <td>
                                    @if($item->is_active == 1)
                                        <span class="badge badge-light-success">Aktif</span>
                                    @else
                                        <span class="badge badge-light-danger">Tidak Aktif</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light-primary btn-active-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $item->id }}">Edit</button>
                                    <form action="{{ route('academic-years.destroy', $item->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger btn-active-danger confirm-delete" >Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            
                            <!-- Edit Modal -->
                            <div class="modal fade drawer-modal" id="editModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('academic-years.update', $item->id) }}" method="POST">
                                            @csrf @method('PUT')
                                            <div class="modal-header"><h2 class="fw-bold">Edit Tahun Ajaran</h2>
                                                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                                                    <i class="ki-outline ki-cross fs-1 text-dark"></i>
                                                </div>
                                            </div>
                                            <div class="modal-body">
                                                <div class="fv-row mb-7"><label class="required fs-6 fw-semibold mb-2">Tahun Ajaran</label><input type="text" name="name" class="form-control form-control-solid" value="{{ $item->name }}" required></div>
                                                <div class="fv-row mb-7"><label class="required fs-6 fw-semibold mb-2">Semester</label><input type="text" name="semester" class="form-control form-control-solid" value="{{ $item->semester }}" required></div>
                                                <div class="fv-row mb-7">
                                                    <label class="required fs-6 fw-semibold mb-2">Status Aktif</label>
                                                    <select name="is_active" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#editModal{{ $item->id }}" required>
                                                        <option value="1" {{ $item->is_active == 1 ? 'selected' : '' }}>Aktif</option>
                                                        <option value="0" {{ $item->is_active == 0 ? 'selected' : '' }}>Tidak Aktif</option>
                                                    </select>
                                                </div>
                                                
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
            <form action="{{ route('academic-years.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h2 class="fw-bold">Tambah Tahun Ajaran</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1 text-dark"></i>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="fv-row mb-7"><label class="required fs-6 fw-semibold mb-2">Tahun Ajaran</label><input type="text" name="name" class="form-control form-control-solid" required></div>
                    <div class="fv-row mb-7"><label class="required fs-6 fw-semibold mb-2">Semester</label><input type="text" name="semester" class="form-control form-control-solid" required></div>
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Status Aktif</label>
                        <select name="is_active" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#addModal" required>
                            <option value="1">Aktif</option>
                            <option value="0">Tidak Aktif</option>
                        </select>
                    </div>
                    
                </div>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

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