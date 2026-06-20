@extends(auth()->user()->hasRole('Siswa') ? 'frontend.layout.app' : 'backend.layout.app')
@section('title', 'Penugasan Siswa')
@section('content')

<!--begin::Toolbar-->
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <!--begin::Toolbar container-->
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <!--begin::Page title-->
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Penugasan Siswa</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">
                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Home</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted">Akademik</li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted">Penugasan</li>
            </ul>
        </div>
        <!--end::Page title-->
        <!--begin::Actions-->
        <div class="d-flex align-items-center gap-2 gap-lg-3">
            @hasanyrole('Superadmin|Guru')
            <button type="button" class="btn btn-sm fw-bold btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                Tambah Tugas
            </button>
            @endhasanyrole
        </div>
        <!--end::Actions-->
    </div>
</div>
<!--end::Toolbar-->

<!--begin::Content-->
<div id="kt_app_content" class="app-content flex-column-fluid">
    <!--begin::Content container-->
    <div id="kt_app_content_container" class="app-container container-xxl">
        <!-- Header Konten -->
        <div class="mb-10">
            <h1 class="text-gray-900 fw-bold mb-1">Daftar Penugasan Siswa</h1>
            <div class="text-muted fw-semibold fs-6">Kelola tugas, instruksi, dan pantau pengumpulan jawaban siswa secara real-time</div>
        </div>
        
        <!-- Library Grid -->
        <div class="row g-6 g-xl-9">
            @forelse($items as $item)
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card h-100 card-custom border-0 shadow-sm overflow-hidden card-hover">
                    <!-- Assignment Cover -->
                    @php
                        $isPast = \Carbon\Carbon::parse($item->due_date)->isPast();
                        $bgColor = $isPast ? '#f1416c' : '#7239ea';
                    @endphp
                    <div class="position-relative d-flex flex-center h-150px w-100" style="background-color: {{ $bgColor }};">
                        <div class="text-center p-5">
                            <i class="ki-outline ki-notepad-edit fs-5x text-white opacity-25 position-absolute top-50 start-50 translate-middle"></i>
                            <div class="position-relative z-index-1">
                                <span class="badge badge-light-white fw-bold mb-2">TUGAS</span>
                                <h4 class="text-white fw-bolder mb-0 px-5 text-truncate-2">{{ $item->title }}</h4>
                            </div>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="card-body p-6">
                        <div class="mb-5">
                            <div class="d-flex align-items-center mb-1">
                                <span class="text-gray-800 fw-bold fs-6">{{ $item->teachingAssignment->subject->name ?? '-' }}</span>
                            </div>
                            <div class="fw-semibold text-gray-500 fs-7">Kelas: {{ $item->teachingAssignment->classRoom->name ?? '-' }}</div>
                        </div>

                        <div class="separator separator-dashed my-4"></div>

                        <div class="d-flex flex-stack">
                            <div class="d-flex flex-column">
                                <span class="text-gray-400 fw-bold fs-8 text-uppercase">Deadline</span>
                                <span class="fw-bold fs-7 {{ $isPast ? 'text-danger' : 'text-gray-800' }}">
                                    {{ \Carbon\Carbon::parse($item->due_date)->format('d M Y') }}
                                </span>
                            </div>
                            @hasanyrole('Superadmin|Guru')
                            <div class="d-flex flex-column text-end">
                                <span class="text-gray-400 fw-bold fs-8 text-uppercase">Terkumpul</span>
                                <span class="text-gray-800 fw-bold fs-7">{{ $item->submissions->count() }} Siswa</span>
                            </div>
                            @endhasanyrole
                            @hasrole('Siswa')
                            @php
                                $mySubmission = $item->submissions->where('student_id', auth()->user()->student->id)->first();
                            @endphp
                            <div class="d-flex flex-column text-end">
                                <span class="text-gray-400 fw-bold fs-8 text-uppercase">Status</span>
                                @if($mySubmission)
                                    <span class="badge badge-light-success fw-bold">Selesai</span>
                                @else
                                    <span class="badge badge-light-warning fw-bold">Belum</span>
                                @endif
                            </div>
                            @endhasrole
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="card-footer p-2 border-0 bg-light-dark bg-opacity-10">
                        <div class="d-flex justify-content-between align-items-center px-2">
                            <div class="d-flex gap-1">
                                @if($item->file_path)
                                <a href="{{ Storage::url($item->file_path) }}" class="btn btn-sm btn-icon btn-active-color-primary" target="_blank" title="Lihat Lampiran PDF">
                                    <i class="ki-outline ki-file-down fs-2"></i>
                                </a>
                                @endif
                                <a href="{{ route('assignments.show', $item->id) }}" class="btn btn-sm btn-light-primary fw-bold px-4">
                                    Detail
                                </a>
                            </div>
                            
                            @hasanyrole('Superadmin|Guru')
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-icon btn-active-color-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $item->id }}">
                                    <i class="ki-outline ki-pencil fs-2"></i>
                                </button>
                                <form action="{{ route('assignments.destroy', $item->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-active-color-danger confirm-delete" >
                                        <i class="ki-outline ki-trash fs-2"></i>
                                    </button>
                                </form>
                            </div>
                            @endhasanyrole
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="card border-dashed border-gray-300">
                                <div class="card-header mt-5 border-0 pt-6">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">Daftar Penugasan Siswa</h3>
                </div>
            </div>
            <div class="card-body">
                        <div class="text-center px-4 py-15">
                            <img src="{{ asset('assets/media/illustrations/sigma-1/5.png') }}" alt="" class="mw-100 mh-200px mb-7">
                            <h3 class="fw-bold text-gray-900 mb-2">Belum ada penugasan siswa</h3>
                            <p class="text-gray-400 fs-6 fw-semibold">Daftar tugas, instruksi, dan pantau pengumpulan jawaban siswa belum tersedia.</p>
                        </div>
                    </div>
                </div>
            </div>
            @endforelse
        </div>
    </div>
</div>

<style>
    .card-hover { transition: transform 0.3s ease; }
    .card-hover:hover { transform: translateY(-10px); cursor: pointer; }
    .text-truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>

<!-- Add Modal -->
<div class="modal fade drawer-modal" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <form action="{{ route('assignments.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h2 class="fw-bold">Buat Penugasan Baru</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body px-10 py-10">
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Pilih Kelas / Mapel</label>
                        <select name="teaching_assignment_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#addModal" required>
                            <option value="">Pilih...</option>
                            @foreach($assignments as $a)
                                <option value="{{ $a->id }}">{{ $a->classRoom->name }} - {{ $a->subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Judul Tugas</label>
                        <input type="text" name="title" class="form-control form-control-solid" required>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="fs-6 fw-semibold mb-2">Deskripsi / Instruksi</label>
                        <textarea name="description" class="form-control form-control-solid" rows="4"></textarea>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Batas Waktu (Deadline)</label>
                        <input type="datetime-local" name="due_date" class="form-control form-control-solid" required>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="fs-6 fw-semibold mb-2">Lampiran Tugas (Opsional)</label>
                        <input type="file" name="file" class="form-control form-control-solid">
                    </div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="submit" class="btn btn-primary">Simpan Tugas</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($items as $item)
<!-- Edit Modal -->
<div class="modal fade drawer-modal" id="editModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <form action="{{ route('assignments.update', $item->id) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h2 class="fw-bold">Edit Penugasan</h2>
                </div>
                <div class="modal-body px-10 py-10">
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Judul Tugas</label>
                        <input type="text" name="title" class="form-control form-control-solid" value="{{ $item->title }}" required>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="fs-6 fw-semibold mb-2">Deskripsi</label>
                        <textarea name="description" class="form-control form-control-solid" rows="4">{{ $item->description }}</textarea>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Deadline</label>
                        <div class="position-relative d-flex align-items-center">
                            <i class="ki-outline ki-calendar-8 fs-2 position-absolute mx-4"></i>
                            <input type="text" name="due_date" class="form-control form-control-solid ps-12 kt_flatpickr_edit" value="{{ \Carbon\Carbon::parse($item->due_date)->format('Y-m-d H:i') }}" placeholder="Pilih Tanggal & Waktu" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="submit" class="btn btn-primary">Update Tugas</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<script>
    $(document).ready(function() {
        $("#kt_flatpickr_due_date").flatpickr({
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
        });

        $(".kt_flatpickr_edit").flatpickr({
            enableTime: true,
            dateFormat: "Y-m-d H:i",
        });
    });
</script>


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