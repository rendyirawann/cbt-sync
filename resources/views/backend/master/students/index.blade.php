@extends('backend.layout.app')
@section('title', 'Data Siswa')
@section('content')
<div class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <div class="card card-flush mt-6 mt-xl-9">
            <div class="card-header mt-5">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">Manajemen Siswa</h3>
                </div>
                <div class="card-toolbar">
                    @include('backend.master._import_tools', ['templateRoute' => 'students.template', 'importRoute' => 'students.import', 'label' => 'Siswa'])
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Siswa</button>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle fw-bold">
                        <thead class="fs-7 text-gray-400 text-uppercase">
                            <tr>
                                <th>NISN</th>
                                <th>Nama Lengkap</th>
                                <th>Asal Sekolah</th>
                                <th>Email (Akun Login)</th>
                                <th>Gender</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="fs-6">
                            @forelse($students as $item)
                            <tr>
                                <td>{{ $item->nisn }}</td>
                                <td>{{ $item->user->name ?? '-' }}</td>
                                <td>{{ $item->school->name ?? '-' }}</td>
                                <td>{{ $item->user->email ?? '-' }}</td>
                                <td>{{ $item->gender == 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                                <td class="text-end">
                                    <a href="#" class="btn btn-sm btn-light-primary btn-active-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $item->id }}">Edit</a>
                                    <form action="{{ route('students.destroy', $item->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger btn-active-danger confirm-delete" >Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6">
                                    <div class="text-center px-4 py-15">
                                        <img src="{{ asset('assets/media/illustrations/sigma-1/5.png') }}" alt="" class="mw-100 mh-200px mb-7">
                                        <h3 class="fw-bold text-gray-900 mb-2">Belum ada data siswa</h3>
                                        <p class="text-gray-400 fs-6 fw-semibold">Daftar peserta didik belum tersedia di sistem. Silakan tambahkan akun siswa baru.</p>
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
            <form action="{{ route('students.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h2 class="fw-bold">Tambah Siswa Baru</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1 text-dark"></i>
                    </div>
                </div>
                <div class="modal-body">
                    <h5 class="mb-4 text-primary">Informasi Akun (Login)</h5>
                    <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">Nama Lengkap</label><input type="text" name="name" class="form-control form-control-solid" required></div>
                    <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">Email</label><input type="email" name="email" class="form-control form-control-solid" required></div>
                    <div class="fv-row mb-7"><label class="required fs-6 fw-semibold mb-2">Password</label><input type="password" name="password" class="form-control form-control-solid" required></div>
                    
                    <h5 class="mb-4 text-primary border-top pt-4">Profil Siswa</h5>
                    <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">Sekolah Asal</label>
                        <select name="school_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#addModal" required>
                            <option value="">Pilih Sekolah...</option>
                            @foreach($schools as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>

                            @endforeach
                        </select>
                    </div>
                    <div class="fv-row mb-5">
                        <label class="required fs-6 fw-semibold mb-2">NISN</label>
                        <input type="text" name="nisn" class="form-control form-control-solid" required>
                        <div class="text-muted fs-7 mt-2">NISN ini otomatis digunakan sebagai <b>Username</b> untuk login akun Siswa.</div>
                    </div>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Telepon</label><input type="text" name="phone" class="form-control form-control-solid"></div>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Jenis Kelamin</label>
                        <select name="gender" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#addModal">
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Alamat</label><textarea name="address" class="form-control form-control-solid"></textarea></div>

                    <h5 class="mb-4 text-primary border-top pt-4">Data Orang Tua / Wali</h5>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Nama Orang Tua</label><input type="text" name="parent_name" class="form-control form-control-solid" placeholder="Contoh: Bpk. Heru"></div>
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <label class="fs-6 fw-semibold mb-2">Email Orang Tua</label>
                            <input type="email" name="parent_email" class="form-control form-control-solid" placeholder="email@orangtua.com">
                        </div>
                        <div class="col-md-6">
                            <label class="fs-6 fw-semibold mb-2">No. WA Orang Tua</label>
                            <input type="text" name="parent_phone" class="form-control form-control-solid" placeholder="0812xxxx">
                        </div>
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

@foreach($students as $item)

<!-- Edit Modal -->
<div class="modal fade drawer-modal" id="editModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('students.update', $item->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h2 class="fw-bold">Edit Siswa</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1 text-dark"></i>
                    </div>
                </div>
                <div class="modal-body">
                    <h5 class="mb-4 text-primary">Informasi Akun (Login)</h5>
                    <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">Nama Lengkap</label><input type="text" name="name" class="form-control form-control-solid" value="{{ $item->user->name ?? '' }}" required></div>
                    <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">Email</label><input type="email" name="email" class="form-control form-control-solid" value="{{ $item->user->email ?? '' }}" required></div>
                    <div class="fv-row mb-7"><label class="fs-6 fw-semibold mb-2">Password (Kosongkan jika tidak diubah)</label><input type="password" name="password" class="form-control form-control-solid"></div>
                    
                    <h5 class="mb-4 text-primary border-top pt-4">Profil Siswa</h5>
                    <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">Sekolah Asal</label>
                        <select name="school_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#editModal{{ $item->id }}" required>
                            @foreach($schools as $s)
                                <option value="{{ $s->id }}" {{ $item->school_id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fv-row mb-5">
                        <label class="required fs-6 fw-semibold mb-2">NISN</label>
                        <input type="text" name="nisn" class="form-control form-control-solid" value="{{ $item->nisn }}" required>
                    </div>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Telepon</label><input type="text" name="phone" class="form-control form-control-solid" value="{{ $item->phone }}"></div>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Jenis Kelamin</label>
                        <select name="gender" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#editModal{{ $item->id }}">
                            <option value="L" {{ $item->gender == 'L' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="P" {{ $item->gender == 'P' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                    </div>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Alamat</label><textarea name="address" class="form-control form-control-solid">{{ $item->address }}</textarea></div>

                    <h5 class="mb-4 text-primary border-top pt-4">Data Orang Tua / Wali</h5>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Nama Orang Tua</label><input type="text" name="parent_name" class="form-control form-control-solid" value="{{ $item->parent_name }}"></div>
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <label class="fs-6 fw-semibold mb-2">Email Orang Tua</label>
                            <input type="email" name="parent_email" class="form-control form-control-solid" value="{{ $item->parent_email }}">
                        </div>
                        <div class="col-md-6">
                            <label class="fs-6 fw-semibold mb-2">No. WA Orang Tua</label>
                            <input type="text" name="parent_phone" class="form-control form-control-solid" value="{{ $item->parent_phone }}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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