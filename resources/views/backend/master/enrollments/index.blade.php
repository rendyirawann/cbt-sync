@extends('backend.layout.app')
@section('title', 'Rombongan Belajar')
@section('content')

<div class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        <div class="card card-flush mt-6 mt-xl-9">
            <div class="card-header mt-5">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">Plotting Siswa (Rombel)</h3>
                    <div class="fs-6 text-gray-500">Manajemen penempatan siswa ke dalam kelas</div>
                </div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Plotting Siswa Baru</button>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle fw-bold">
                        <thead class="fs-7 text-gray-400 text-uppercase">
                            <tr>
                                <th>Siswa</th>
                                <th>Sekolah Asal</th>
                                <th>Kelas (Rombel)</th>
                                <th>Tahun Ajaran</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="fs-6">
                            @forelse($enrollments as $item)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="symbol symbol-45px me-5">
                                            <div class="symbol-label fs-2 fw-semibold bg-light-primary text-primary">{{ substr($item->student->user->name ?? 'S', 0, 1) }}</div>
                                        </div>
                                        <div class="d-flex justify-content-start flex-column">
                                            <span class="text-gray-800 fw-bold fs-6">{{ $item->student->user->name ?? '-' }}</span>
                                            <span class="text-muted fw-semibold d-block fs-7">NISN: {{ $item->student->nisn ?? '-' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $item->classRoom->school->name ?? '-' }}</td>
                                <td>
                                    <span class="badge badge-light-primary fs-7">{{ $item->classRoom->name ?? '-' }}</span>
                                </td>
                                <td>{{ $item->academicYear->name ?? '-' }} (Sem {{ $item->academicYear->semester ?? '-' }})</td>
                                <td class="text-end">
                                    <form action="{{ route('enrollments.destroy', $item->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-icon btn-sm btn-light-danger btn-active-danger confirm-delete"  title="Hapus dari Rombel">
                                            <i class="ki-outline ki-trash fs-2"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5">
                                    <div class="text-center px-4 py-15">
                                        <img src="{{ asset('assets/media/illustrations/sigma-1/5.png') }}" alt="" class="mw-100 mh-200px mb-7">
                                        <h3 class="fw-bold text-gray-900 mb-2">Belum ada rombongan belajar</h3>
                                        <p class="text-gray-400 fs-6 fw-semibold">Data penempatan siswa ke dalam kelas (Rombel) belum tersedia. Silakan lakukan plotting siswa.</p>
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('enrollments.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h2 class="fw-bold">Plotting Siswa Massal</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1 text-dark"></i>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fs-6 fw-semibold mb-2">Tahun Ajaran</label>
                            <select name="academic_year_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#addModal" required>
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ $activeAY && $activeAY->id == $ay->id ? 'selected' : '' }}>{{ $ay->name }} (Sem {{ $ay->semester }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fs-6 fw-semibold mb-2">Pilih Kelas Tujuan</label>
                            <select name="class_room_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#addModal" required>
                                <option value="">Pilih Kelas...</option>
                                @foreach($classRooms as $cr)
                                    <option value="{{ $cr->id }}">{{ $cr->name }} ({{ $cr->school->name ?? '-' }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Pilih Siswa</label>
                        <select name="student_ids[]" class="form-select form-select-solid" data-control="select2" data-close-on-select="false" data-placeholder="Cari siswa..." data-dropdown-parent="#addModal" multiple="multiple" required>
                            @foreach($students as $s)
                                <option value="{{ $s->id }}">{{ $s->user->name ?? '-' }} (NISN: {{ $s->nisn }})</option>
                            @endforeach
                        </select>
                        <div class="text-muted fs-7 mt-2">Menampilkan siswa yang belum memiliki kelas di tahun ajaran aktif.</div>
                    </div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Plotting Siswa</button>
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