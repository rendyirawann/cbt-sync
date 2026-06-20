@extends(auth()->user()->hasRole('Siswa') ? 'frontend.layout.app' : 'backend.layout.app')
@section('title', 'Modul: ' . $item->title)

@section('content')

<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">{{ $item->title }}</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">Portal</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">
                    <a href="{{ route('student.learning-modules.index') }}" class="text-muted text-hover-primary">Modul Belajar</a>
                </li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">Detail</li>
            </ul>
        </div>
        <div class="d-flex align-items-center gap-2 gap-lg-3">
            @if($item->zoom_link)
                <a href="{{ $item->zoom_link }}" target="_blank" class="btn btn-sm btn-success fw-bold">
                    <i class="ki-outline ki-video fs-4 me-1"></i> Masuk Kelas Virtual
                </a>
            @endif
            <a href="{{ route('student.learning-modules.download', $item->id) }}" class="btn btn-sm btn-primary fw-bold">
                <i class="ki-outline ki-cloud-download fs-4 me-1"></i> Unduh Modul
            </a>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        <div class="row g-7">

            {{-- ============================== --}}
            {{-- MAIN CONTENT --}}
            {{-- ============================== --}}
            <div class="col-lg-8">

                {{-- Module Preview Card --}}
                <div class="card mb-8 shadow-sm border-0">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <div class="d-flex align-items-center">
                                <div class="symbol symbol-50px me-4">
                                    <span class="symbol-label bg-light-primary">
                                        <i class="ki-outline ki-document fs-2x text-primary"></i>
                                    </span>
                                </div>
                                <div>
                                    <h3 class="fw-bolder text-dark mb-0">{{ $item->title }}</h3>
                                    <span class="text-muted fs-7">{{ $item->teachingAssignment->subject->name ?? '-' }} &bull; {{ $item->teachingAssignment->teacher->user->name ?? '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        @php
                            $extension = strtolower($item->file_type);
                            $fileUrl   = Storage::url($item->file_path);
                        @endphp

                        @if($extension === 'pdf')
                            <div class="bg-light rounded p-3 mb-5" style="height: 550px; overflow: auto;">
                                <div id="pdf-loading" class="d-flex flex-center h-100">
                                    <span class="spinner-border text-primary" style="width:3rem;height:3rem;"></span>
                                </div>
                                <canvas id="pdf-render" class="shadow-sm mx-auto d-block d-none"></canvas>
                                <div class="d-flex justify-content-center gap-3 mt-3" id="pdf-controls" style="display:none!important;">
                                    <button id="prevPage" class="btn btn-sm btn-light-dark"><i class="ki-outline ki-arrow-left fs-4"></i> Prev</button>
                                    <span id="pageNum" class="btn btn-sm btn-light fw-bold">Page 1</span>
                                    <button id="nextPage" class="btn btn-sm btn-light-dark">Next <i class="ki-outline ki-arrow-right fs-4"></i></button>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-20 bg-light rounded border border-dashed border-gray-400 mb-5">
                                <i class="ki-outline ki-document fs-5x text-gray-300 mb-5"></i>
                                <h4 class="text-gray-800 fw-bold">Pratinjau tidak tersedia untuk format {{ strtoupper($extension) }}</h4>
                                <p class="text-muted mb-5">Silakan unduh file untuk membaca materi secara lengkap.</p>
                                <a href="{{ route('student.learning-modules.download', $item->id) }}" class="btn btn-primary fw-bold">
                                    <i class="ki-outline ki-cloud-download fs-4 me-1"></i> Download Sekarang
                                </a>
                            </div>
                        @endif

                        @if($item->description)
                        <div class="separator my-6"></div>
                        <div>
                            <h5 class="fw-bolder text-dark mb-3"><i class="ki-outline ki-information-5 text-primary me-2"></i>Deskripsi Materi</h5>
                            <div class="text-gray-700 fs-6 bg-light rounded p-5">
                                {!! nl2br(e($item->description)) !!}
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- ============================== --}}
                {{-- FORUM DISKUSI --}}
                {{-- ============================== --}}
                <div class="card shadow-sm border-0" id="forum-section">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <h3 class="fw-bolder text-dark">
                                <i class="ki-outline ki-message-text-2 text-primary me-2 fs-2"></i>
                                Forum Diskusi
                                <span class="badge badge-light-primary ms-2" id="comment-count">{{ $item->comments->count() }}</span>
                            </h3>
                        </div>
                    </div>
                    <div class="card-body pt-2">

                        {{-- Comment Form --}}
                        <div class="d-flex align-items-start mb-8 bg-light-primary rounded p-5">
                            <div class="symbol symbol-40px me-4">
                                <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}" class="rounded-circle">
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark mb-2">{{ auth()->user()->name }}</div>
                                <form id="comment-form">
                                    @csrf
                                    <input type="hidden" name="learning_module_id" value="{{ $item->id }}">
                                    <textarea name="comment" id="comment-input" rows="3"
                                        class="form-control form-control-solid mb-3"
                                        placeholder="Tanyakan sesuatu tentang materi ini... atau bagikan pendapatmu!"></textarea>
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary btn-sm fw-bold" id="btn-submit">
                                            <i class="ki-outline ki-send fs-4 me-1"></i> Kirim Komentar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="separator mb-8"></div>

                        {{-- Comments List --}}
                        <div id="comments-container">
                            @forelse($item->comments as $comment)
                            <div class="d-flex mb-8" id="comment-{{ $comment->id }}">
                                <div class="symbol symbol-40px me-4 flex-shrink-0">
                                    <img src="{{ $comment->user->avatar_url }}" alt="{{ $comment->user->name }}" class="rounded-circle">
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div>
                                            <span class="text-dark fw-bolder fs-6">{{ $comment->user->name }}</span>
                                            @if($comment->user->hasRole('Guru'))
                                                <span class="badge badge-light-success ms-2 fs-9">Guru</span>
                                            @endif
                                        </div>
                                        <span class="text-muted fs-8">{{ $comment->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="bg-light rounded p-4 text-gray-700 fs-6 mb-2">
                                        {{ $comment->comment }}
                                    </div>
                                    <button class="btn btn-link btn-sm text-muted p-0 fw-bold btn-reply" data-id="{{ $comment->id }}">
                                        <i class="ki-outline ki-message-text fs-5 me-1"></i> Balas
                                    </button>

                                    {{-- Replies --}}
                                    @foreach($comment->replies as $reply)
                                    <div class="d-flex mt-4 ps-5 border-start border-2 border-light-primary">
                                        <div class="symbol symbol-35px me-3 flex-shrink-0">
                                            <img src="{{ $reply->user->avatar_url }}" alt="{{ $reply->user->name }}" class="rounded-circle">
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <div>
                                                    <span class="text-dark fw-bolder fs-7">{{ $reply->user->name }}</span>
                                                    @if($reply->user->hasRole('Guru'))
                                                        <span class="badge badge-light-success ms-2 fs-9">Guru</span>
                                                    @endif
                                                </div>
                                                <span class="text-muted fs-8">{{ $reply->created_at->diffForHumans() }}</span>
                                            </div>
                                            <div class="bg-light-dark bg-opacity-10 rounded p-3 text-gray-700 fs-7">
                                                {{ $reply->comment }}
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach

                                    {{-- Reply Form (hidden) --}}
                                    <div class="reply-form d-none mt-3 ps-5" id="reply-form-{{ $comment->id }}">
                                        <div class="d-flex align-items-start">
                                            <div class="symbol symbol-35px me-3 flex-shrink-0">
                                                <img src="{{ auth()->user()->avatar_url }}" alt="" class="rounded-circle">
                                            </div>
                                            <div class="flex-grow-1">
                                                <textarea class="form-control form-control-solid form-control-sm mb-2 reply-input"
                                                    rows="2" placeholder="Tulis balasan..." data-parent="{{ $comment->id }}"></textarea>
                                                <div class="d-flex gap-2">
                                                    <button class="btn btn-primary btn-sm fw-bold btn-send-reply" data-parent="{{ $comment->id }}">
                                                        Kirim Balasan
                                                    </button>
                                                    <button class="btn btn-light btn-sm btn-cancel-reply">Batal</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center py-10" id="no-comments">
                                <i class="ki-outline ki-message-question fs-4x text-gray-200 mb-5"></i>
                                <p class="text-muted fw-semibold">Belum ada diskusi. Jadilah yang pertama bertanya!</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>{{-- END MAIN --}}

            {{-- ============================== --}}
            {{-- SIDEBAR --}}
            {{-- ============================== --}}
            <div class="col-lg-4">

                {{-- Info Modul --}}
                <div class="card shadow-sm border-0 mb-7">
                    <div class="card-header border-0 pt-6">
                        <h4 class="card-title fw-bolder text-dark">Informasi Modul</h4>
                    </div>
                    <div class="card-body pt-2">
                        <div class="d-flex flex-stack py-3 border-bottom border-gray-100">
                            <span class="text-gray-500 fw-semibold fs-7">Mata Pelajaran</span>
                            <span class="text-dark fw-bolder fs-7 text-end">{{ $item->teachingAssignment->subject->name ?? '-' }}</span>
                        </div>
                        <div class="d-flex flex-stack py-3 border-bottom border-gray-100">
                            <span class="text-gray-500 fw-semibold fs-7">Pengajar</span>
                            <span class="text-dark fw-bolder fs-7 text-end">{{ $item->teachingAssignment->teacher->user->name ?? '-' }}</span>
                        </div>
                        <div class="d-flex flex-stack py-3 border-bottom border-gray-100">
                            <span class="text-gray-500 fw-semibold fs-7">Format</span>
                            <span class="badge badge-light-danger fw-bold">{{ strtoupper($extension) }}</span>
                        </div>
                        <div class="d-flex flex-stack py-3 border-bottom border-gray-100">
                            <span class="text-gray-500 fw-semibold fs-7">Ukuran File</span>
                            <span class="text-dark fw-bolder fs-7">{{ $item->formatted_file_size }}</span>
                        </div>
                        <div class="d-flex flex-stack py-3">
                            <span class="text-gray-500 fw-semibold fs-7">Diunggah</span>
                            <span class="text-dark fw-bolder fs-7">{{ $item->created_at->format('d M Y') }}</span>
                        </div>
                        <div class="d-grid mt-5">
                            <a href="{{ route('student.learning-modules.download', $item->id) }}" class="btn btn-primary fw-bold">
                                <i class="ki-outline ki-cloud-download fs-4 me-1"></i> Download Materi
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Ajak diskusi --}}
                <div class="card shadow-sm border-0 overflow-hidden">
                    <div class="card-body p-0">
                        <div class="p-8 text-center" style="background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);">
                            <i class="ki-outline ki-message-text-2 fs-3x text-white opacity-75 mb-4"></i>
                            <h4 class="text-white fw-bolder mb-2">Ada Pertanyaan?</h4>
                            <p class="text-white opacity-75 mb-5 fs-7">Gunakan forum diskusi untuk bertanya langsung kepada guru atau berdiskusi bersama teman sekelas.</p>
                            <a href="#forum-section" class="btn btn-white btn-color-primary fw-bold w-100">
                                <i class="ki-outline ki-arrow-down fs-5 me-1"></i> Mulai Diskusi
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Pesan ke Guru --}}
                <div class="card shadow-sm border-0 mt-7">
                    <div class="card-header border-0 pt-5">
                        <h4 class="card-title fw-bolder text-dark">Hubungi Guru</h4>
                    </div>
                    <div class="card-body pt-2">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-45px symbol-circle me-4">
                                <img src="{{ $item->teachingAssignment->teacher->user->avatar_url ?? 'https://ui-avatars.com/api/?name=G&background=6a11cb&color=fff' }}" alt="">
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark fs-6">{{ $item->teachingAssignment->teacher->user->name ?? 'Guru' }}</div>
                                <div class="text-muted fs-8">Guru Pengampu</div>
                            </div>
                        </div>
                        <div class="d-grid mt-5">
                            <a href="{{ route('student.chat.index') }}" class="btn btn-light-primary fw-bold btn-sm">
                                <i class="ki-outline ki-messages fs-4 me-1"></i> Kirim Pesan
                            </a>
                        </div>
                    </div>
                </div>

            </div>{{-- END SIDEBAR --}}

        </div>
    </div>
</div>

@push('scripts')
@if($extension === 'pdf')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
    pdfjsLib.GlobalWorkerOptions.workerSrc = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";
    let pdfDoc = null, currentPage = 1;
    const fileUrl = '{{ $fileUrl }}';

    pdfjsLib.getDocument(fileUrl).promise.then(pdf => {
        pdfDoc = pdf;
        document.getElementById('pdf-loading').classList.add('d-none');
        document.getElementById('pdf-render').classList.remove('d-none');
        document.getElementById('pdf-controls').style.removeProperty('display');
        renderPage(1);
    }).catch(() => {
        document.getElementById('pdf-loading').innerHTML = `
            <div class="text-center">
                <i class="ki-outline ki-document fs-4x text-danger mb-3"></i>
                <h4 class="text-danger fw-bold">Gagal memuat Pratinjau PDF</h4>
                <p class="text-muted fs-7">Jika ini adalah data bawaan (dummy), file fisiknya mungkin tidak ada di server.<br>Silakan edit modul ini dan unggah file PDF yang asli.</p>
            </div>
        `;
    });

    function renderPage(num) {
        pdfDoc.getPage(num).then(page => {
            const container = document.getElementById('pdf-render').parentElement;
            const scale = (container.clientWidth - 32) / page.getViewport({scale: 1}).width;
            const viewport = page.getViewport({scale: Math.min(scale, 1.5)});
            const canvas = document.getElementById('pdf-render');
            canvas.height = viewport.height;
            canvas.width  = viewport.width;
            page.render({canvasContext: canvas.getContext('2d'), viewport});
            document.getElementById('pageNum').textContent = `Hal. ${num} / ${pdfDoc.numPages}`;
        });
    }
    document.getElementById('prevPage').onclick = () => { if(currentPage > 1) renderPage(--currentPage); };
    document.getElementById('nextPage').onclick = () => { if(currentPage < pdfDoc.numPages) renderPage(++currentPage); };
</script>
@endif

<script>
    // ===== KIRIM KOMENTAR BARU =====
    $('#comment-form').on('submit', function(e) {
        e.preventDefault();
        const comment = $('#comment-input').val().trim();
        if (!comment) return;

        const btn = $('#btn-submit');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.post("{{ route('module-comments.store') }}", {
            _token: "{{ csrf_token() }}",
            learning_module_id: "{{ $item->id }}",
            comment: comment
        }).done(function(res) {
            if (res.status === 'success') {
                $('#no-comments').remove();
                const d = res.data;
                const html = `
                <div class="d-flex mb-8" id="comment-${d.id}">
                    <div class="symbol symbol-40px me-4 flex-shrink-0">
                        <img src="${d.avatar_url}" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-dark fw-bolder fs-6">${d.user_name}</span>
                            <span class="text-muted fs-8">${d.created_at}</span>
                        </div>
                        <div class="bg-light rounded p-4 text-gray-700 fs-6 mb-2">${d.comment}</div>
                        <button class="btn btn-link btn-sm text-muted p-0 fw-bold btn-reply" data-id="${d.id}">
                            <i class="ki-outline ki-message-text fs-5 me-1"></i> Balas
                        </button>
                        <div class="reply-form d-none mt-3 ps-5" id="reply-form-${d.id}">
                            <div class="d-flex align-items-start">
                                <div class="symbol symbol-35px me-3 flex-shrink-0">
                                    <img src="{{ auth()->user()->avatar_url }}" class="rounded-circle" style="width:35px;height:35px;object-fit:cover;">
                                </div>
                                <div class="flex-grow-1">
                                    <textarea class="form-control form-control-solid form-control-sm mb-2 reply-input" rows="2" placeholder="Tulis balasan..." data-parent="${d.id}"></textarea>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-primary btn-sm fw-bold btn-send-reply" data-parent="${d.id}">Kirim Balasan</button>
                                        <button class="btn btn-light btn-sm btn-cancel-reply">Batal</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
                $('#comments-container').prepend(html);
                $('#comment-input').val('');
                const count = parseInt($('#comment-count').text()) + 1;
                $('#comment-count').text(count);
            }
        }).fail(function() {
            Swal.fire('Gagal!', 'Terjadi kesalahan saat mengirim komentar.', 'error');
        }).always(function() {
            btn.prop('disabled', false).html('<i class="ki-outline ki-send fs-4 me-1"></i> Kirim Komentar');
        });
    });

    // ===== TOGGLE FORM BALASAN =====
    $(document).on('click', '.btn-reply', function() {
        const id = $(this).data('id');
        $(`#reply-form-${id}`).toggleClass('d-none');
    });

    $(document).on('click', '.btn-cancel-reply', function() {
        $(this).closest('.reply-form').addClass('d-none');
    });

    // ===== KIRIM BALASAN =====
    $(document).on('click', '.btn-send-reply', function() {
        const btn = $(this);
        const parentId = btn.data('parent');
        const replyText = btn.closest('.reply-form').find('.reply-input').val().trim();
        if (!replyText) return;

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.post("{{ route('module-comments.store') }}", {
            _token: "{{ csrf_token() }}",
            learning_module_id: "{{ $item->id }}",
            comment: replyText,
            parent_id: parentId
        }).done(function(res) {
            if (res.status === 'success') {
                const d = res.data;
                const replyHtml = `
                <div class="d-flex mt-4 ps-5 border-start border-2 border-light-primary">
                    <div class="symbol symbol-35px me-3 flex-shrink-0">
                        <img src="${d.avatar_url}" class="rounded-circle" style="width:35px;height:35px;object-fit:cover;">
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-dark fw-bolder fs-7">${d.user_name}</span>
                            <span class="text-muted fs-8">${d.created_at}</span>
                        </div>
                        <div class="bg-light-dark bg-opacity-10 rounded p-3 text-gray-700 fs-7">${d.comment}</div>
                    </div>
                </div>`;
                $(`#reply-form-${parentId}`).before(replyHtml);
                $(`#reply-form-${parentId}`).addClass('d-none');
                $(`#reply-form-${parentId}`).find('.reply-input').val('');
            }
        }).fail(function() {
            Swal.fire('Gagal!', 'Terjadi kesalahan.', 'error');
        }).always(function() {
            btn.prop('disabled', false).text('Kirim Balasan');
        });
    });
</script>
@endpush
@endsection
