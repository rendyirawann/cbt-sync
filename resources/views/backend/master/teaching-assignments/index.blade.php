@extends('backend.layout.app')
@section('title', 'Penugasan Guru')
@section('content')
<div class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        <div class="card card-flush mt-6 mt-xl-9">
            <div class="card-header mt-5">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">Penugasan Mengajar Guru</h3>
                    <div class="fs-6 text-gray-500">Plotting Guru ke Kelas dan Mata Pelajaran</div>
                </div>
                <div class="card-toolbar">
                    @include('backend.master._import_tools', ['templateRoute' => 'teaching-assignments.template', 'importRoute' => 'teaching-assignments.import', 'label' => 'Penugasan Guru'])
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Penugasan</button>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle fw-bold">
                        <thead class="fs-7 text-gray-400 text-uppercase">
                            <tr>
                                <th>Guru</th>
                                <th>Mata Pelajaran</th>
                                <th>Kelas (Sekolah)</th>
                                <th>Tahun Ajaran</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="fs-6">
                            @foreach($assignments as $item)
                            <tr>
                                <td>
                                    {{ $item->teacher->user->name ?? '-' }}
                                    <span class="text-muted d-block fs-7">NIP: {{ $item->teacher->nip ?? '-' }}</span>
                                </td>
                                <td>{{ $item->subject->name ?? '-' }}</td>
                                <td>
                                    {{ $item->classRoom->name ?? '-' }} ({{ $item->classRoom->level ?? '-' }})
                                    <span class="text-muted d-block fs-7">{{ $item->classRoom->school->name ?? '-' }}</span>
                                </td>
                                <td>{{ $item->academicYear->name ?? '-' }} (Sem {{ $item->academicYear->semester ?? '-' }})</td>
                                <td class="text-end">
                                    <a href="#" class="btn btn-sm btn-light-primary btn-active-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $item->id }}">Edit</a>
                                        <form action="{{ route('teaching-assignments.destroy', $item->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger btn-active-danger confirm-delete" >Hapus</button>
                                    </form>
                                </td>
                            </tr>

                            @endforeach
                            @if($assignments->isEmpty())
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">Belum ada penugasan guru.</td>
                            </tr>
                            @endif
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
            <form action="{{ route('teaching-assignments.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h2 class="fw-bold">Tambah Penugasan Guru</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1 text-dark"></i>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Pilih Guru</label>
                        <select name="teacher_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#addModal" data-placeholder="Pilih Guru..." required>
                            <option value=""></option>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}">{{ $t->user->name ?? '-' }} (NIP: {{ $t->nip }})</option>

                            @endforeach
                        </select>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Pilih Mata Pelajaran</label>
                        <select name="subject_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#addModal" data-placeholder="Pilih Pelajaran..." required>
                            <option value=""></option>
                            @foreach($subjects as $s)
                                <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->code }})</option>

                            @endforeach
                        </select>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Pilih Kelas</label>
                        <select name="class_room_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#addModal" data-placeholder="Pilih Kelas..." required>
                            <option value=""></option>
                            @foreach($classRooms as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} - {{ $c->school->name ?? '-' }}</option>

                            @endforeach
                        </select>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Tahun Ajaran Aktif</label>
                        <select name="academic_year_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#addModal" data-placeholder="Pilih Tahun Ajaran..." required>
                            <option value=""></option>
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}">{{ $ay->name }} (Semester {{ $ay->semester }})</option>

                            @endforeach
                        </select>
                    </div>
                    
                </div>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Penugasan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($assignments as $item)

<!-- Edit Modal -->
<div class="modal fade drawer-modal" id="editModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('teaching-assignments.update', $item->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h2 class="fw-bold">Edit Penugasan</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1 text-dark"></i>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Pilih Guru</label>
                        <select name="teacher_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#editModal{{ $item->id }}" required>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" {{ $item->teacher_id == $t->id ? 'selected' : '' }}>{{ $t->user->name ?? '-' }} (NIP: {{ $t->nip }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Pilih Mata Pelajaran</label>
                        <select name="subject_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#editModal{{ $item->id }}" required>
                            @foreach($subjects as $s)
                                <option value="{{ $s->id }}" {{ $item->subject_id == $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Pilih Kelas</label>
                        <select name="class_room_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#editModal{{ $item->id }}" required>
                            @foreach($classRooms as $c)
                                <option value="{{ $c->id }}" {{ $item->class_room_id == $c->id ? 'selected' : '' }}>{{ $c->name }} - {{ $c->school->name ?? '-' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Tahun Ajaran Aktif</label>
                        <select name="academic_year_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#editModal{{ $item->id }}" required>
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" {{ $item->academic_year_id == $ay->id ? 'selected' : '' }}>{{ $ay->name }} (Semester {{ $ay->semester }})</option>
                            @endforeach
                        </select>
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