@extends(auth()->user()->hasRole('Siswa') ? 'frontend.layout.app' : 'backend.layout.app')
@section('title', 'Modul Pembelajaran')
@section('content')

<!--begin::Toolbar-->
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Modul Pembelajaran</h1>
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
                <li class="breadcrumb-item text-muted">Modul Digital</li>
            </ul>
        </div>
        <div class="d-flex align-items-center gap-2 gap-lg-3">
            @hasanyrole('Superadmin|Guru')
            <button type="button" class="btn btn-sm fw-bold btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="ki-outline ki-plus fs-2 me-1"></i> Unggah Modul Baru
            </button>
            @endhasanyrole
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        <div class="mb-10">
            <h1 class="text-gray-900 fw-bold mb-1">Perpustakaan Modul Digital</h1>
            <div class="text-muted fw-semibold fs-6">Kumpulan materi belajar dan modul pendukung untuk membantu proses pembelajaran siswa</div>
        </div>
        
        <div class="row g-6 g-xl-9">
            @forelse($items as $item)
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card h-100 card-custom border-0 shadow-sm overflow-hidden card-hover">
                    @php
                        $colors = ['#F1416C', '#0095E8', '#50CD89', '#7239EA', '#FFAD0F'];
                        $bgColor = $colors[$loop->index % count($colors)];
                        $extension = strtolower(pathinfo($item->file_path, PATHINFO_EXTENSION));
                        $fileUrl = Storage::url($item->file_path);
                    @endphp
                    <div class="position-relative d-flex flex-center h-200px w-100" style="background-color: {{ $bgColor }};">
                        <div class="text-center p-5">
                            <i class="ki-outline ki-file-text fs-5x text-white opacity-25 position-absolute top-50 start-50 translate-middle"></i>
                            <div class="position-relative z-index-1">
                                <span class="badge badge-light-white fw-bold mb-3">{{ strtoupper($extension) }}</span>
                                <h4 class="text-white fw-bolder mb-0 px-5 text-truncate-2" style="max-height: 3.6em; overflow: hidden;">{{ $item->title }}</h4>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-6">
                        <div class="mb-5">
                            <div class="d-flex align-items-center mb-1">
                                <span class="text-gray-800 fw-bold fs-6">{{ $item->teachingAssignment->subject->name ?? '-' }}</span>
                            </div>
                            <div class="fw-semibold text-gray-500 fs-7">Kelas: {{ $item->teachingAssignment->classRoom->name ?? '-' }}</div>
                        </div>

                        <div class="d-flex flex-stack mt-auto">
                            <div class="d-flex flex-column me-2">
                                <span class="text-gray-400 fw-bold fs-8 text-uppercase">Ukuran</span>
                                <span class="text-gray-800 fw-bold fs-7">{{ $item->formatted_file_size }}</span>
                            </div>
                            <div class="d-flex flex-column text-end" style="max-width: 60%;">
                                <span class="text-gray-400 fw-bold fs-8 text-uppercase">Oleh</span>
                                <span class="text-gray-800 fw-bold fs-7 text-truncate">{{ $item->teachingAssignment->teacher->user->name ?? 'Guru' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer p-2 border-0 bg-light-dark bg-opacity-10">
                        <div class="d-flex justify-content-between align-items-center px-2">
                            <div class="d-flex gap-1 align-items-center flex-wrap">
                                <a href="{{ route('student.learning-modules.show', $item->id) }}" class="btn btn-sm btn-light-primary fw-bold text-nowrap" title="Detail & Diskusi">
                                    <i class="ki-outline ki-message-text-2 fs-3 me-1"></i> Diskusi
                                </a>
                                <button type="button" class="btn btn-sm btn-icon btn-light-primary" 
                                    onclick="initViewer('{{ $fileUrl }}', '{{ $extension }}', '{{ $item->title }}')" 
                                    title="Pratinjau Modul">
                                    <i class="ki-outline ki-eye fs-2"></i>
                                </button>
                                <a href="{{ route('learning-modules.download', $item->id) }}" class="btn btn-sm btn-icon btn-primary" title="Download">
                                    <i class="ki-outline ki-cloud-download fs-2"></i>
                                </a>
                            </div>
                            @hasanyrole('Superadmin|Guru')
                            <div class="d-flex gap-1 align-items-center flex-wrap">
                                <button class="btn btn-sm btn-icon btn-light-warning" data-bs-toggle="modal" data-bs-target="#editModal{{ $item->id }}">
                                    <i class="ki-outline ki-pencil fs-2"></i>
                                </button>
                                <form action="{{ route('learning-modules.destroy', $item->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-icon btn-light-danger confirm-delete" >
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
                    <h3 class="fw-bold mb-1">Daftar Modul Pembelajaran</h3>
                </div>
            </div>
            <div class="card-body">
                        <div class="text-center px-4 py-15">
                            <img src="{{ asset('assets/media/illustrations/sigma-1/5.png') }}" alt="" class="mw-100 mh-200px mb-7">
                            <h3 class="fw-bold text-gray-900 mb-2">Belum ada modul di perpustakaan</h3>
                            <p class="text-gray-400 fs-6 fw-semibold">Kumpulan materi belajar dan modul pendukung untuk membantu proses pembelajaran siswa belum tersedia.</p>
                        </div>
                    </div>
                </div>
            </div>
            @endforelse
        </div>
    </div>
</div>

<!-- Modal Preview Modul -->
<div class="modal fade" id="modal_viewer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen p-9">
        <div class="modal-content border-0 shadow-none">
            <div class="modal-header">
                <h3 class="modal-title" id="viewer_title">Pratinjau Modul</h3>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-outline ki-cross fs-1"></i>
                </div>
            </div>
            <div class="modal-body bg-light p-0 position-relative overflow-hidden">
                <div id="pdf-viewer-container" class="viewer-section d-none h-100 flex-column">
                    <div class="d-flex flex-center bg-dark p-2 gap-5 z-index-2 position-sticky top-0 shadow-sm">
                        <button id="prevPage" class="btn btn-sm btn-icon btn-light-dark btn-active-color-primary"><i class="ki-outline ki-arrow-left fs-2"></i></button>
                        <span id="pageNum" class="text-white fw-bold fs-6">Halaman 1 / 1</span>
                        <button id="nextPage" class="btn btn-sm btn-icon btn-light-dark btn-active-color-primary"><i class="ki-outline ki-arrow-right fs-2"></i></button>
                    </div>
                    <div class="flex-grow-1 overflow-auto d-flex flex-center p-10">
                        <div class="pdf-loader spinner-border text-primary position-absolute d-none" role="status" style="width: 3rem; height: 3rem;"></div>
                        <canvas id="pdf-render" class="shadow-lg bg-white"></canvas>
                    </div>
                </div>
                <div id="office-viewer-container" class="viewer-section d-none h-100 overflow-auto p-10 bg-white">
                    <div id="office-render" class="mw-800px mx-auto"></div>
                </div>
                <div id="fallback-viewer" class="viewer-section d-none h-100 flex-center">
                    <div class="text-center">
                        <i class="ki-outline ki-information-5 fs-5x text-warning mb-5"></i>
                        <h3 class="text-gray-800">Pratinjau tidak tersedia untuk format ini</h3>
                        <p class="text-muted">Silakan unduh file untuk melihat konten lengkap.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.6.0/mammoth.browser.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";
    let pdfDoc = null, currentPage = 1, pageRendering = false, pageNumPending = null;

    function initViewer(url, ext, title) {
        document.getElementById('viewer_title').textContent = title;
        document.querySelectorAll('.viewer-section').forEach(el => el.classList.add('d-none'));
        if (ext === 'pdf') loadPdf(url);
        else if (ext === 'docx' || ext === 'doc') loadWord(url);
        else if (ext === 'xlsx' || ext === 'xls') loadExcel(url);
        else document.getElementById('fallback-viewer').classList.remove('d-none');
        $('#modal_viewer').modal('show');
    }

    function loadPdf(url) {
        document.getElementById('pdf-viewer-container').classList.remove('d-none');
        const canvas = document.getElementById('pdf-render');
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        pdfjsLib.getDocument(url).promise.then(pdf => { pdfDoc = pdf; renderPdfPage(1); });
    }

    function renderPdfPage(num) {
        pageRendering = true;
        document.querySelector('.pdf-loader').classList.remove('d-none');
        pdfDoc.getPage(num).then(page => {
            const viewport = page.getViewport({ scale: 1.5 });
            const canvas = document.getElementById('pdf-render');
            canvas.height = viewport.height; canvas.width = viewport.width;
            page.render({ canvasContext: canvas.getContext('2d'), viewport: viewport }).promise.then(() => {
                pageRendering = false;
                document.querySelector('.pdf-loader').classList.add('d-none');
                document.getElementById('pageNum').textContent = `Halaman ${num} / ${pdfDoc.numPages}`;
            });
        });
    }

    document.getElementById('prevPage').addEventListener('click', () => { if (currentPage <= 1) return; currentPage--; renderPdfPage(currentPage); });
    document.getElementById('nextPage').addEventListener('click', () => { if (currentPage >= pdfDoc.numPages) return; currentPage++; renderPdfPage(currentPage); });

    function loadWord(url) {
        document.getElementById('office-viewer-container').classList.remove('d-none');
        fetch(url).then(res => res.arrayBuffer()).then(buffer => {
            mammoth.convertToHtml({arrayBuffer: buffer}).then(result => {
                document.getElementById('office-render').innerHTML = `<div class="p-10 shadow-sm bg-white rounded">${result.value}</div>`;
            });
        });
    }

    function loadExcel(url) {
        document.getElementById('office-viewer-container').classList.remove('d-none');
        fetch(url).then(res => res.arrayBuffer()).then(buffer => {
            const wb = XLSX.read(buffer, {type: 'array'});
            const html = XLSX.utils.sheet_to_html(wb.Sheets[wb.SheetNames[0]]);
            document.getElementById('office-render').innerHTML = `<div class="table-responsive p-5 bg-white rounded shadow-sm">${html}</div>`;
        });
    }
</script>

<style>
    .card-hover { transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .card-hover:hover { transform: translateY(-10px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
    .text-truncate-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>

<!-- Add Modal -->
<div class="modal fade drawer-modal" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog mw-650px">
        <div class="modal-content">
            <form action="{{ route('learning-modules.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h2 class="fw-bold">Unggah Modul Baru</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body px-10 py-10">
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Pilih Kelas / Penugasan</label>
                        <select name="teaching_assignment_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#addModal" required>
                            <option value="">Pilih Penugasan...</option>
                            @foreach($assignments as $a)
                                <option value="{{ $a->id }}">{{ $a->classRoom->name }} - {{ $a->subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Judul Modul</label>
                        <input type="text" name="title" class="form-control form-control-solid" required>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="fs-6 fw-semibold mb-2">Deskripsi (Opsional)</label>
                        <textarea name="description" class="form-control form-control-solid" rows="3"></textarea>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="fs-6 fw-semibold mb-2">Link Zoom Kelas Virtual (Opsional)</label>
                        <input type="url" name="zoom_link" class="form-control form-control-solid" placeholder="https://zoom.us/j/...">
                    </div>
                    <div class="fv-row mb-10">
                        <label class="required fs-6 fw-semibold mb-2">Pilih File Modul</label>
                        <input type="file" name="file" class="form-control form-control-solid" required>
                    </div>
                    <div class="fv-row">
                        <div class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="is_published" value="1" id="flexSwitchDefault" checked/>
                            <label class="form-check-label" for="flexSwitchDefault">
                                Publikasikan Modul (Terlihat oleh siswa)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="submit" class="btn btn-primary">Unggah Modul</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($items as $item)
<div class="modal fade drawer-modal" id="editModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog mw-650px">
        <div class="modal-content">
            <form action="{{ route('learning-modules.update', $item->id) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h2 class="fw-bold">Edit Modul</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body px-10 py-10">
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Pilih Kelas / Penugasan</label>
                        <select name="teaching_assignment_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#editModal{{ $item->id }}" required>
                            <option value="">Pilih Penugasan...</option>
                            @foreach($assignments as $a)
                                <option value="{{ $a->id }}" {{ $item->teaching_assignment_id == $a->id ? 'selected' : '' }}>{{ $a->classRoom->name }} - {{ $a->subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Judul Modul</label>
                        <input type="text" name="title" class="form-control form-control-solid" value="{{ $item->title }}" required>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="fs-6 fw-semibold mb-2">Deskripsi (Opsional)</label>
                        <textarea name="description" class="form-control form-control-solid" rows="3">{{ $item->description }}</textarea>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="fs-6 fw-semibold mb-2">Link Zoom Kelas Virtual (Opsional)</label>
                        <input type="url" name="zoom_link" class="form-control form-control-solid" value="{{ $item->zoom_link }}" placeholder="https://zoom.us/j/...">
                    </div>
                    <div class="fv-row mb-10">
                        <label class="fs-6 fw-semibold mb-2">Ganti File Modul (Opsional)</label>
                        <input type="file" name="file" class="form-control form-control-solid">
                        <div class="text-muted fs-7 mt-2">Biarkan kosong jika tidak ingin mengubah file modul. (File saat ini: {{ $item->file_name }})</div>
                    </div>
                    <div class="fv-row">
                        <div class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="is_published" value="1" id="flexSwitch{{ $item->id }}" {{ $item->is_published ? 'checked' : '' }}/>
                            <label class="form-check-label" for="flexSwitch{{ $item->id }}">
                                Publikasikan Modul (Terlihat oleh siswa)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-center">
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