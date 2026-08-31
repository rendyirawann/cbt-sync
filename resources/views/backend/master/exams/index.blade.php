@extends('backend.layout.app')
@section('title', 'Ujian / CBT')

@section('content')
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 my-0">Ujian / CBT</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">Akademik</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">Ujian Online</li>
            </ul>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExamModal">
                <i class="ki-outline ki-plus fs-3"></i> Buat Ujian
            </button>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        <div class="card">
            <div class="card-body py-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                <th>Judul Ujian</th>
                                <th>Mata Pelajaran / Kelas</th>
                                <th class="text-center">Tipe</th>
                                <th class="text-center">Soal</th>
                                <th class="text-center">Sesi</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 fw-semibold">
                            @forelse($exams as $exam)
                            <tr>
                                <td class="fw-bold text-gray-900">{{ $exam->title }}</td>
                                <td>
                                    {{ $exam->teachingAssignment->subject->name ?? '-' }}
                                    <span class="text-muted d-block fs-7">{{ $exam->teachingAssignment->classRoom->name ?? '-' }}</span>
                                </td>
                                <td class="text-center">
                                    @php $tlabel = ['mixed'=>'PG + Essay','mc'=>'Pilihan Ganda','essay'=>'Essay'][$exam->type] ?? $exam->type; @endphp
                                    <span class="badge badge-light-info">{{ $tlabel }}</span>
                                </td>
                                <td class="text-center">{{ $exam->questions_count }}</td>
                                <td class="text-center">{{ $exam->sessions_count }}</td>
                                <td class="text-center">
                                    <span class="badge badge-light-{{ $exam->status === 'published' ? 'success' : 'warning' }}">
                                        {{ $exam->status === 'published' ? 'Terbit' : 'Draft' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('exams.show', $exam->id) }}" class="btn btn-sm btn-light-primary">
                                        <i class="ki-outline ki-setting-3 fs-5"></i> Kelola
                                    </a>
                                    <form action="{{ route('exams.destroy', $exam->id) }}" method="POST" class="d-inline custom-ajax-confirm">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger btn-delete"><i class="ki-outline ki-trash fs-5"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center py-10 text-muted">Belum ada ujian. Klik "Buat Ujian" untuk memulai.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== Modal Buat Ujian ===== --}}
<div class="modal fade drawer-modal" id="addExamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('exams.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h3 class="modal-title">Buat Ujian Baru</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div>
                </div>
                <div class="modal-body px-8 py-8">
                    <div class="mb-5">
                        <label class="form-label required">Mata Pelajaran / Kelas (Penugasan)</label>
                        <select name="teaching_assignment_id" class="form-select" required>
                            <option value="">Pilih penugasan...</option>
                            @foreach($assignments as $ta)
                                <option value="{{ $ta->id }}">{{ $ta->subject->name ?? '-' }} — {{ $ta->classRoom->name ?? '-' }}@isset($ta->teacher) ({{ $ta->teacher->user->name ?? '' }})@endisset</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-5">
                        <label class="form-label required">Judul Ujian</label>
                        <input type="text" name="title" class="form-control" placeholder="cth: Ulangan Harian Bab 1" required>
                    </div>
                    <div class="mb-5">
                        <label class="form-label">Deskripsi / Instruksi</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-5">
                            <label class="form-label required">Kategori Ujian</label>
                            <select name="type" class="form-select" required>
                                <option value="mixed">Pilihan Ganda + Essay</option>
                                <option value="mc" selected>Pilihan Ganda saja</option>
                                <option value="essay">Essay saja</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-5">
                            <label class="form-label required">Mode Penilaian</label>
                            <select name="points_mode" class="form-select" required>
                                <option value="per_question" selected>Per soal (nilai tiap soal ditentukan)</option>
                                <option value="equal">Bagi rata otomatis (100 / jumlah soal)</option>
                                <option value="manual">Manual (guru tentukan nilai akhir)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-5">
                            <label class="form-label">Pengurang nilai bila salah (mode bagi rata)</label>
                            <input type="number" step="0.01" name="wrong_penalty" class="form-control" value="0">
                        </div>
                        <div class="col-md-6 mb-5">
                            <label class="form-label">KKM / Nilai Lulus</label>
                            <input type="number" step="0.01" name="pass_score" class="form-control" value="75">
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="normalize" id="normChk" checked>
                        <label class="form-check-label" for="normChk">Normalisasi nilai akhir ke skala 0–100</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Buat & Lanjut Tambah Soal</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Konfirmasi hapus pada form .custom-ajax-confirm (di-skip oleh handler global karena tidak .drawer-modal)
    document.querySelectorAll('.custom-ajax-confirm .btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e){
            e.preventDefault();
            const form = this.closest('form');
            Swal.fire({title:'Hapus ujian?', text:'Soal & sesi terkait ikut terhapus.', icon:'warning', showCancelButton:true, confirmButtonText:'Ya, hapus', cancelButtonText:'Batal', confirmButtonColor:'#d33'})
              .then(r => { if(r.isConfirmed) form.submit(); });
        });
    });
</script>
@endpush
@endsection
