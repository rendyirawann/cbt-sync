@extends('backend.layout.app')
@section('title', 'Kelola Ujian: ' . $exam->title)

@section('content')
@php
    $typeLabel = ['mixed'=>'PG + Essay','mc'=>'Pilihan Ganda','essay'=>'Essay'][$exam->type] ?? $exam->type;
    $modeLabel = ['manual'=>'Manual (poin diisi guru)','auto'=>'Otomatis (poin dibagi rata)'][$exam->points_mode] ?? $exam->points_mode;
    // Ujian terkunci begitu ada minimal 1 siswa yang memulai (attempt). Soal & publish dikunci.
    $locked = $exam->hasStartedAttempts();
@endphp

@include('partials.katex')
@include('partials.math-editor')

<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0">{{ $exam->title }}</h1>
            {{-- Breadcrumb berjenjang: jalan pintas cepat kembali ke daftar ujian --}}
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">Akademik</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item"><a href="{{ route('exams.index') }}" class="text-primary text-hover-dark">Ujian / CBT</a></li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-gray-700">{{ \Illuminate\Support\Str::limit($exam->title, 40) }}</li>
            </ul>
            <span class="text-muted fs-7 pt-1">
                {{ $exam->teachingAssignment->subject->name ?? '-' }} • {{ $exam->teachingAssignment->classRoom->name ?? '-' }}
                • <span class="badge badge-light-info">{{ $typeLabel }}</span>
                • Penilaian: {{ $modeLabel }}
            </span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('exams.index') }}" class="btn btn-sm btn-light"><i class="ki-outline ki-arrow-left fs-4"></i> Kembali</a>
            <button class="btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#editExamModal" @disabled($locked)><i class="ki-outline ki-setting-2 fs-5"></i> Pengaturan</button>
            @if($locked)
                <span class="btn btn-sm btn-light-success disabled"><i class="ki-outline ki-lock-2 fs-5"></i> Terbit & Terkunci</span>
            @else
                <form action="{{ route('exams.publish', $exam->id) }}" method="POST" class="custom-ajax-confirm d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-{{ $exam->status === 'published' ? 'warning' : 'success' }}">
                        <i class="ki-outline ki-{{ $exam->status === 'published' ? 'arrow-down' : 'send' }} fs-5"></i>
                        {{ $exam->status === 'published' ? 'Tarik ke Draft' : 'Terbitkan' }}
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        @if($exam->status === 'draft')
        <div class="alert bg-light-warning border border-warning border-dashed d-flex flex-wrap align-items-center mb-6 p-5">
            <i class="ki-outline ki-information-5 fs-2x text-warning me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
            <div class="flex-grow-1 me-3">
                <h4 class="fw-bold text-gray-900 mb-1">Ujian masih DRAFT — siswa belum bisa melihatnya</h4>
                <span class="text-gray-700">Begitu soal & sesi siap, klik <b>Terbitkan</b> supaya ujian muncul di portal siswa.</span>
            </div>
            <form action="{{ route('exams.publish', $exam->id) }}" method="POST">@csrf
                <button type="submit" class="btn btn-success"><i class="ki-outline ki-send fs-5"></i> Terbitkan Sekarang</button>
            </form>
        </div>
        @endif
        @if($locked)
        <div class="alert bg-light-primary border border-primary border-dashed d-flex align-items-center mb-6 p-5">
            <i class="ki-outline ki-lock-2 fs-2x text-primary me-4"><span class="path1"></span><span class="path2"></span></i>
            <div>
                <h4 class="fw-bold text-gray-900 mb-1">Ujian Terkunci</h4>
                <span class="text-gray-700">Sudah ada siswa yang memulai ujian, jadi <b>soal tidak bisa diubah</b>, ujian <b>tidak bisa ditarik ke draft</b>, dan sesi yang sudah dimulai tidak bisa dihapus/diubah. Anda tetap bisa memeriksa & menilai jawaban.</span>
            </div>
        </div>
        @endif
        <ul class="nav nav-tabs nav-line-tabs fs-5 fw-bold mb-6">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab_soal">📝 Soal ({{ $exam->questions->count() }})</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab_sesi">🗓️ Sesi & Jadwal ({{ $exam->sessions->count() }})</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab_hasil">📊 Hasil & Nilai</a></li>
        </ul>

        <div class="tab-content">
            {{-- ================= TAB SOAL ================= --}}
            <div class="tab-pane fade show active" id="tab_soal">
                <div class="d-flex flex-wrap gap-2 mb-5">
                    @if($locked)
                        <span class="text-muted fs-7"><i class="ki-outline ki-lock-2 fs-5 text-primary me-1"></i> Soal terkunci — sudah ada peserta yang memulai.</span>
                    @else
                        @if($exam->hasMc())
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMcModal"><i class="ki-outline ki-plus fs-4"></i> Tambah Pilihan Ganda</button>
                        @endif
                        @if($exam->hasEssay())
                        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#addEssayModal"><i class="ki-outline ki-plus fs-4"></i> Tambah Essay</button>
                        @endif
                        <button class="btn btn-light-warning" data-bs-toggle="modal" data-bs-target="#selectionModal"><i class="ki-outline ki-filter-tick fs-5 me-1"></i>Atur Soal Aktif</button>
                        <button class="btn btn-light-primary" data-bs-toggle="modal" data-bs-target="#bankModal"><i class="ki-outline ki-book-open fs-4"></i> Tarik dari Bank Soal</button>
                    @endif
                    @php $tpl = $exam->type === 'mixed' ? 'mixed' : ($exam->type === 'essay' ? 'essay' : 'pg'); @endphp
                    <div class="d-flex flex-wrap gap-2 ms-auto">
                        <div class="dropdown">
                            <button class="btn btn-light-success" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Unduh template untuk menyiapkan soal secara offline">
                                <i class="ki-outline ki-file-down fs-4"></i> Unduh Template Soal
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('exams.template', $tpl) }}"><i class="ki-outline ki-file fs-5 me-2 text-success"></i> Excel (.xlsx) — teks & rumus</a></li>
                                <li><a class="dropdown-item" href="{{ route('exams.word-template', $tpl) }}"><i class="ki-outline ki-file fs-5 me-2 text-primary"></i> Word (.docx) — bisa tempel gambar</a></li>
                            </ul>
                        </div>
                        @unless($locked)
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal" title="Unggah template (Excel/Word) yang sudah diisi">
                            <i class="ki-outline ki-file-up fs-4"></i> Upload Template (Impor Soal)
                        </button>
                        @endunless
                    </div>
                </div>

                @unless($locked)
                {{-- ===== Modal Impor Soal dari Excel ===== --}}
                <div class="modal fade drawer-modal" id="importModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog"><div class="modal-content">
                        <form action="{{ route('exams.import', $exam->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-header"><h3 class="modal-title">Impor Soal dari Excel</h3><div class="btn btn-icon btn-sm" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div></div>
                            <div class="modal-body px-8 py-6">
                                <div class="alert alert-light-primary fs-8 py-3 mb-5">
                                    Gunakan <b>Template Soal</b> di atas, isi pada lembar/tabel <b>"Soal"</b>, lalu unggah di sini.
                                    Rumus tulis di antara <code>$ … $</code>.
                                    <b>Excel</b>: untuk gambar, impor dulu lalu tambahkan lewat tombol <b>Edit</b> soal.
                                    <b>Word</b>: gambar bisa <b>ditempel langsung</b> ke dalam sel (soal/opsi).
                                    @if($exam->type==='mc') Kategori ujian ini <b>Pilihan Ganda</b> — baris essay akan dilewati.
                                    @elseif($exam->type==='essay') Kategori ujian ini <b>Essay</b> — baris PG akan dilewati.
                                    @endif
                                </div>
                                <label class="form-label required">Berkas Excel / Word (.xlsx, .xls, .docx)</label>
                                <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.docx" required>
                                <div class="form-text">Maksimal 8 MB. Soal yang diimpor ditambahkan ke soal yang sudah ada.</div>
                            </div>
                            <div class="modal-footer"><button type="submit" class="btn btn-success"><i class="ki-outline ki-file-up fs-5"></i> Impor Sekarang</button></div>
                        </form>
                    </div></div>
                </div>
                {{-- ===== Modal Atur Soal Aktif =====
                     Guru/Admin memilih cara soal diberikan: semua, hanya yang
                     dicentang, atau sejumlah soal ACAK per siswa. --}}
                <div class="modal fade drawer-modal" id="selectionModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog"><div class="modal-content">
                        <form action="{{ route('exams.question-selection', $exam->id) }}" method="POST">
                            @csrf
                            <div class="modal-header"><h3 class="modal-title">Atur Soal yang Diujikan</h3>
                                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
                            </div>
                            <div class="modal-body px-8 py-6">
                                @php $aktifCount = $exam->questions->where('is_active', true)->count(); @endphp
                                <div class="alert alert-light-primary fs-8 py-2 mb-5">
                                    Ujian ini punya <b>{{ $exam->questions->count() }}</b> soal
                                    (<b>{{ $aktifCount }}</b> ditandai aktif).
                                    Pengaturan hanya bisa diubah selama belum ada siswa yang memulai ujian.
                                </div>

                                <div class="mb-5">
                                    <label class="form-label required">Cara pemilihan soal</label>
                                    <div class="d-flex flex-column gap-3">
                                        <label class="form-check">
                                            <input class="form-check-input sel-mode" type="radio" name="question_selection" value="all"
                                                @checked(($exam->question_selection ?? 'all') === 'all')>
                                            <span class="form-check-label ms-2"><b>Semua soal</b> — setiap siswa mengerjakan seluruh soal.</span>
                                        </label>
                                        <label class="form-check">
                                            <input class="form-check-input sel-mode" type="radio" name="question_selection" value="manual"
                                                @checked($exam->question_selection === 'manual')>
                                            <span class="form-check-label ms-2"><b>Pilih manual</b> — hanya soal yang dicentang di bawah.</span>
                                        </label>
                                        <label class="form-check">
                                            <input class="form-check-input sel-mode" type="radio" name="question_selection" value="auto"
                                                @checked($exam->question_selection === 'auto')>
                                            <span class="form-check-label ms-2"><b>Otomatis (acak per siswa)</b> — tiap siswa menerima
                                                sejumlah soal acak dari kolam yang aktif, jadi paketnya berbeda-beda.</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-5" id="selCount">
                                    <label class="form-label">Jumlah soal per siswa</label>
                                    <input type="number" min="1" name="active_question_count" class="form-control form-control-solid"
                                        value="{{ $exam->active_question_count }}" placeholder="mis. 30 dari {{ $exam->questions->count() }} soal">
                                    <div class="form-text">Dipakai hanya pada mode otomatis. Nilai akhir tetap berskala 0–100
                                        karena bobot dihitung di dalam paket masing-masing siswa.</div>
                                </div>

                                <div id="selList">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label mb-0">Soal yang diujikan</label>
                                        <label class="form-check form-check-sm">
                                            <input class="form-check-input" type="checkbox" id="selAll">
                                            <span class="form-check-label fs-8 ms-2">Pilih semua</span>
                                        </label>
                                    </div>
                                    <div style="max-height:44vh;overflow:auto">
                                        @foreach($exam->questions->sortBy('order') as $i => $qq)
                                            <label class="d-flex align-items-start gap-3 border rounded p-3 mb-2">
                                                <input class="form-check-input mt-1 sel-item" type="checkbox" name="active[]"
                                                    value="{{ $qq->id }}" @checked($qq->is_active)>
                                                <div class="flex-grow-1">
                                                    <div class="d-flex flex-wrap gap-2 mb-1">
                                                        <span class="badge badge-light-{{ $qq->type==='mc'?'primary':'info' }}">{{ $qq->type==='mc'?'PG':'Essay' }}</span>
                                                        <span class="badge badge-light">soal {{ $i + 1 }}</span>
                                                    </div>
                                                    <div class="fs-7 text-gray-800">{{ \Illuminate\Support\Str::limit($qq->question_text, 120) }}</div>
                                                </div>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" @disabled($exam->hasStartedAttempts())>Simpan Pengaturan</button>
                            </div>
                        </form>
                    </div></div>
                </div>

                {{-- ===== Modal Tarik dari Bank Soal Bersama ===== --}}
                <div class="modal fade drawer-modal" id="bankModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog"><div class="modal-content">
                        <form action="{{ route('exams.pull-bank', $exam->id) }}" method="POST">
                            @csrf
                            <div class="modal-header"><h3 class="modal-title">Tarik dari Bank Soal — {{ $exam->teachingAssignment->subject->name ?? '' }}</h3><div class="btn btn-icon btn-sm" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div></div>
                            <div class="modal-body px-8 py-6">
                                <div class="alert alert-light-primary fs-8 py-2 mb-4">Soal dari <b>Bank Soal Bersama</b> (mapel & tingkat sama, boleh dari sekolah mana pun). Soal yang ditarik <b>disalin</b> menjadi milik ujian ini.</div>
                                <input type="text" id="bankSearch" class="form-control form-control-sm mb-3" placeholder="🔎 Ketik untuk menyaring soal...">
                                @if($bankQuestions->isEmpty())
                                    <div class="text-center text-muted py-8">Belum ada soal Bank yang cocok. Soal
                                        ditawarkan hanya bila <b>mapel dan tingkatnya sama</b> dengan ujian ini, dan
                                        masuk ke Bank otomatis saat guru menyusun ujian.</div>
                                @else
                                    <div class="d-flex justify-content-between mb-3">
                                        <label class="form-check form-check-sm"><input class="form-check-input" type="checkbox" id="bankCheckAll"><span class="form-check-label fs-8 ms-2">Pilih semua ujian</span></label>
                                        <span class="text-muted fs-8">{{ $bankQuestions->count() }} soal tersedia</span>
                                    </div>
                                    <div style="max-height:52vh;overflow:auto">
                                    {{-- Dikelompokkan per UJIAN asal: pilih dulu ujian sumbernya, lalu centang
                                         semua soal ujian itu sekaligus atau buka dan pilih satu per satu. --}}
                                    @foreach($bankQuestions->groupBy(fn ($b) => $b->source_exam_id ?? 'tanpa') as $grup)
                                        @php $awal = $grup->first(); @endphp
                                        <div class="border rounded mb-3 bank-grup">
                                            <div class="d-flex flex-stack flex-wrap gap-2 bg-light-primary px-3 py-2">
                                                <label class="form-check form-check-sm">
                                                    <input class="form-check-input bank-grup-all" type="checkbox">
                                                    <span class="form-check-label fw-bold fs-7 ms-2">{{ $awal->source_exam_title ?? 'Tanpa ujian asal' }}</span>
                                                </label>
                                                <div class="d-flex flex-wrap align-items-center gap-2">
                                                    <span class="badge badge-light-dark">{{ $awal->school->name ?? 'Tanpa sekolah' }}</span>
                                                    <span class="badge badge-light-primary bank-grup-jml">{{ $grup->count() }} soal</span>
                                                    <button class="btn btn-sm btn-light py-1 px-3" type="button" data-bs-toggle="collapse"
                                                        data-bs-target="#bankGrup{{ $loop->index }}">Lihat soal</button>
                                                </div>
                                            </div>
                                            <div class="collapse p-3" id="bankGrup{{ $loop->index }}">
                                            @foreach($grup as $b)
                                            <label class="d-flex align-items-start gap-3 border rounded p-3 mb-2 bank-item"
                                                data-cari="{{ strtolower($b->question_text . ' ' . ($b->school->name ?? '') . ' ' . ($b->level ?? '') . ' ' . ($b->source_exam_title ?? '')) }}">
                                                <input class="form-check-input mt-1 bank-check" type="checkbox" name="bank_ids[]" value="{{ $b->id }}">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex flex-wrap gap-2 mb-1">
                                                        <span class="badge badge-light-{{ $b->type==='mc'?'primary':'info' }}">{{ $b->type==='mc'?'PG':'Essay' }}</span>
                                                        @if($b->level)<span class="badge badge-light-warning">Tingkat {{ $b->level }}</span>@endif
                                                        <span class="badge badge-light-dark">{{ $b->school->name ?? 'Tanpa sekolah' }}</span>
                                                        @if($b->sourceSchool)<span class="badge badge-light text-muted">sumber: {{ $b->sourceSchool->name }}</span>@endif
                                                        <span class="badge badge-light">{{ rtrim(rtrim((string)$b->points,'0'),'.') }} poin</span>
                                                    </div>
                                                    <div class="fw-semibold text-gray-800 fs-7 bank-text">{{ $b->question_text }}</div>
                                                    @if($b->type==='mc')<div class="text-muted fs-8">{{ $b->options->pluck('option_text')->filter()->take(4)->implode(' · ') }}</div>@endif
                                                </div>
                                            </label>
                                            @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="modal-footer"><button type="submit" class="btn btn-primary" @disabled($bankQuestions->isEmpty())><i class="ki-outline ki-copy fs-5"></i> Salin ke Ujian</button></div>
                        </form>
                    </div></div>
                </div>

                @endunless

                {{-- ===== Komposisi bobot nilai (PG vs Essay) =====
                     Guru sering tidak sadar poin default tiap soal = 1, sehingga PG hanya
                     menyumbang sebagian kecil nilai akhir. Tampilkan proporsi sebenarnya
                     (memakai bobot efektif sesuai mode penilaian) + aksi cepat menyeimbangkan. --}}
                @php
                    $qCount = $exam->questions->count();
                    $mcW = 0; $esW = 0;
                    foreach ($exam->questions as $qq) {
                        $w = \App\Services\CbtScoringService::questionWeight($exam, $qq, $qCount);
                        if ($qq->type === 'mc') { $mcW += $w; } else { $esW += $w; }
                    }
                    $totW = $mcW + $esW;
                    $bothTypes = $mcW > 0 && $esW > 0;
                    $isAuto = $exam->points_mode === 'auto';
                    // Porsi bagian pada nilai akhir SELALU 50:50 untuk ujian campuran
                    // (nilai akhir = rata-rata nilai PG dan nilai Essay).
                    $sw = $exam->sectionWeights();
                    $mcPct = round($sw['mc']);
                    $esPct = round($sw['essay']);
                    $skewed = false;
                    $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
                @endphp
                @if($qCount > 0)
                <div class="card mb-4 border border-dashed {{ $skewed ? 'border-warning' : 'border-gray-300' }}">
                    <div class="card-body py-4">
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <div class="flex-grow-1">
                                <div class="fw-bold text-gray-800 mb-1">
                                    Komposisi nilai
                                    <span class="text-muted fw-normal fs-8">— menentukan porsi tiap bagian pada nilai akhir</span>
                                </div>
                                <div class="fs-7">
                                    <span class="badge badge-light-primary">PG {{ $fmt($mcW) }} poin • {{ $mcPct }}%</span>
                                    <span class="badge badge-light-info ms-1">Essay {{ $fmt($esW) }} poin • {{ $esPct }}%</span>
                                    <span class="text-muted ms-2">total maks {{ $fmt($totW) }} poin</span>
                                </div>
                                <div class="progress mt-2" style="height:6px;max-width:420px">
                                    <div class="progress-bar bg-primary" style="width: {{ $mcPct }}%"></div>
                                    <div class="progress-bar bg-info" style="width: {{ $esPct }}%"></div>
                                </div>
                                @if($isAuto)
                                    <div class="text-success fs-8 mt-2"><i class="ki-outline ki-check-circle fs-6 text-success"></i> Mode <b>Otomatis</b>: poin dibagi rata sistem — tiap bagian bertotal 100. Nilai akhir = <b>(Nilai PG + Nilai Essay) ÷ 2</b>.</div>
                                @else
                                    <div class="text-muted fs-8 mt-2">Mode <b>Manual</b>: poin tiap soal diisi guru. Nilai tiap bagian dihitung 0–100 dari total poin bagiannya, lalu nilai akhir = <b>(Nilai PG + Nilai Essay) ÷ 2</b>.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @forelse($exam->questions as $i => $q)
                <div class="card mb-4">
                    <div class="card-body d-flex">
                        <div class="me-4"><span class="badge badge-circle badge-primary fs-6">{{ $i + 1 }}</span></div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between">
                                @unless($q->is_active)<span class="badge badge-light-danger mb-2 me-1">tidak diujikan</span>@endunless
                                <span class="badge badge-light-{{ $q->type === 'mc' ? 'primary' : 'info' }} mb-2">{{ $q->type === 'mc' ? 'Pilihan Ganda' : 'Essay' }} • {{ rtrim(rtrim((string)$q->points,'0'),'.') }} poin{{ $q->type==='mc' && $q->penalty>0 ? ' / -'.rtrim(rtrim((string)$q->penalty,'0'),'.').' salah' : '' }}</span>
                                @unless($locked)
                                <div>
                                    <button class="btn btn-sm btn-icon btn-light-primary" data-bs-toggle="modal" data-bs-target="#editQ{{ $q->id }}"><i class="ki-outline ki-pencil fs-5"></i></button>
                                    <form action="{{ route('exam-questions.destroy', $q->id) }}" method="POST" class="d-inline custom-ajax-confirm">@csrf @method('DELETE')
                                        <button class="btn btn-sm btn-icon btn-light-danger btn-delete"><i class="ki-outline ki-trash fs-5"></i></button>
                                    </form>
                                </div>
                                @endunless
                            </div>
                            <div class="fw-semibold text-gray-900 mb-2">{!! nl2br(e($q->question_text)) !!}</div>
                            @if($q->image_path)<img src="{{ asset('storage/'.$q->image_path) }}" class="zoomable rounded mb-3 mh-150px">@endif
                            @if($q->type === 'mc')
                                <div class="d-flex flex-column gap-2">
                                    @foreach($q->options as $opt)
                                    <div class="d-flex align-items-start {{ $opt->is_correct ? 'text-success fw-bold' : 'text-gray-700' }}">
                                        <span class="badge badge-{{ $opt->is_correct ? 'success' : 'secondary' }} me-2">{{ $opt->label }}</span>
                                        <div>
                                            @if($opt->option_text){{ $opt->option_text }}@endif
                                            @if($opt->image_path)<img src="{{ asset('storage/'.$opt->image_path) }}" class="zoomable rounded d-block mt-1 mh-80px" alt="Gambar opsi {{ $opt->label }}">@endif
                                        </div>
                                        @if($opt->is_correct)<i class="ki-outline ki-check-circle fs-4 text-success ms-2"></i>@endif
                                    </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- edit modal per soal --}}
                <div class="modal fade drawer-modal" id="editQ{{ $q->id }}" tabindex="-1" data-bs-focus="false" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('exam-questions.update', $q->id) }}" method="POST" enctype="multipart/form-data">
                                @csrf @method('PUT')
                                <div class="modal-header"><h3 class="modal-title">Edit Soal #{{ $i+1 }}</h3><div class="btn btn-icon btn-sm" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div></div>
                                <div class="modal-body px-8 py-6 rdev-math-scope">
                                    <div class="mb-4">
                                        <label class="form-label required">Pertanyaan <span class="text-muted fs-8">(rumus: $ … $)</span></label>
                                        @include('partials.math-toolbar')
                                        <textarea name="question_text" class="form-control math-input" data-preview="#prev_edit_{{ $q->id }}" rows="3" required>{{ $q->question_text }}</textarea>
                                        <div class="math-preview" id="prev_edit_{{ $q->id }}"></div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12 mb-4"><div class="alert alert-light-primary py-2 mb-0 fs-8">
                                            <i class="ki-outline ki-information-5 fs-5 text-primary me-1"></i>
                                            Bobot soal ini dihitung sistem: <b>{{ rtrim(rtrim(number_format((float)\App\Services\CbtScoringService::questionWeight($exam,$q),2,'.',''),'0'),'.') }} poin</b>
                                            @if($q->type === 'essay' && $exam->points_mode !== 'auto') (mode Manual: nilai essay ditentukan guru saat memeriksa, total maksimal 100) @endif
                                        </div></div>
                                    </div>
                                    @if($q->image_path)<div class="mb-2"><img src="{{ asset('storage/'.$q->image_path) }}" class="zoomable rounded mh-100px" alt="Gambar soal"></div>@endif
                                    <div class="mb-4"><label class="form-label">Ganti Gambar Soal (opsional)</label><input type="file" name="image" class="form-control" accept="image/*"><div class="form-text">Format JPG/JPEG/PNG, maksimal 3 MB.</div></div>
                                    @if($q->type === 'mc')
                                    <label class="form-label required">Opsi Jawaban <span class="text-muted fs-8">(klik bulatan = kunci • tiap opsi boleh teks, rumus $…$, dan/atau gambar)</span></label>
                                    <div class="mc-options">
                                        @foreach($q->options as $oi => $opt)
                                        <div class="mc-row border border-gray-300 rounded p-3 mb-2">
                                            <div class="d-flex align-items-start gap-3">
                                                <span class="pt-2"><input class="form-check-input mt-0" type="radio" name="correct" value="{{ $oi }}" title="Tandai sebagai kunci jawaban" {{ $opt->is_correct ? 'checked' : '' }}></span>
                                                <div class="flex-grow-1">
                                                    <input type="hidden" name="option_ids[]" value="{{ $opt->id }}">
                                                    <input type="text" name="options[]" class="form-control math-input mb-2" value="{{ $opt->option_text }}" placeholder="Teks opsi (boleh $rumus$, boleh dikosongkan bila pakai gambar)">
                                                    @if($opt->image_path)
                                                    <div class="d-flex align-items-center gap-3 mb-2">
                                                        <img src="{{ asset('storage/'.$opt->image_path) }}" class="zoomable rounded mh-60px" alt="Gambar opsi {{ $opt->label }}">
                                                        <label class="form-check form-check-sm form-check-custom d-flex align-items-center gap-2 mb-0">
                                                            <input class="form-check-input" type="checkbox" name="option_remove_image[]" value="{{ $opt->id }}">
                                                            <span class="fs-8 text-muted">Hapus gambar</span>
                                                        </label>
                                                    </div>
                                                    @endif
                                                    <input type="file" name="option_images[]" class="form-control form-control-sm" accept="image/*">
                                                </div>
                                                <button type="button" class="btn btn-icon btn-light-danger mc-remove" title="Hapus opsi"><i class="ki-outline ki-trash fs-6"></i></button>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light-primary mc-add"><i class="ki-outline ki-plus fs-6"></i> Tambah opsi</button>
                                    @endif
                                </div>
                                <div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan</button></div>
                            </form>
                        </div>
                    </div>
                </div>
                @empty
                <div class="card"><div class="card-body text-center py-10 text-muted">Belum ada soal. Tambahkan soal di atas.</div></div>
                @endforelse
            </div>

            {{-- ================= TAB SESI ================= --}}
            <div class="tab-pane fade" id="tab_sesi">
                <div class="d-flex mb-5">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSessionModal" @if($exam->questions->count()===0) disabled title="Tambah soal dulu" @endif>
                    @if($belumUjian->isNotEmpty())
                    <button class="btn btn-light-warning" data-bs-toggle="modal" data-bs-target="#makeupSessionModal">
                        <i class="ki-outline ki-calendar-add fs-5 me-1"></i>Jadwalkan Susulan ({{ $belumUjian->count() }})</button>
                    @else
                    <span class="btn btn-light disabled" title="Semua siswa sudah mengikuti ujian ini">
                        <i class="ki-outline ki-check-circle fs-5 me-1"></i>Tidak ada siswa yang perlu susulan</span>
                    @endif
                        <i class="ki-outline ki-plus fs-4"></i> Buat Sesi Ujian
                    </button>
                </div>
                <div class="row g-4">
                    @forelse($exam->sessions as $s)
                    @php
                        $sStarted = $s->attempts->count() > 0;
                        $sFinished = $s->isFinished();
                        // Sesi bisa diedit hanya jika belum ada yang memulai DAN belum terlewat.
                        $sCanEdit = !$sStarted && !$sFinished;
                    @endphp
                    <div class="col-md-6 col-xl-4">
                        <div class="card h-100 {{ $s->is_active ? '' : 'bg-light' }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <h4 class="fw-bold text-gray-900 mb-0">{{ $s->name }}</h4>
                                    <div class="d-flex gap-1">
                                        @unless($s->is_active)<span class="badge badge-light-secondary">Nonaktif</span>@endunless
                                        <span class="badge badge-light-{{ $s->isFinished() ? 'secondary' : ($s->isWithinSchedule() ? 'success' : 'warning') }}">
                                            {{ $s->isFinished() ? 'Selesai' : ($s->isWithinSchedule() ? 'Berlangsung' : 'Terjadwal') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-gray-600 fs-7 mb-1"><i class="ki-outline ki-calendar fs-6 me-1"></i> {{ \Carbon\Carbon::parse($s->starts_at)->format('d M Y H:i') }} – {{ \Carbon\Carbon::parse($s->ends_at)->format('H:i') }}</div>
                                <div class="text-gray-600 fs-7 mb-1"><i class="ki-outline ki-timer fs-6 me-1"></i> {{ $s->duration_minutes }} menit • Kuota: {{ $s->max_capacity ?? '∞' }}</div>
                                <div class="text-gray-600 fs-7 mb-3"><i class="ki-outline ki-people fs-6 me-1"></i> {{ $s->class_room_id ? ($s->classRoom->name ?? 'Kelas') : 'Daftar manual' }} • {{ $s->attempts->count() }} mengerjakan</div>
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span class="badge badge-light-info d-inline-flex align-items-center py-2" title="Berikan PIN ini ke siswa untuk membuka kunci bila ia keluar layar saat ujian">
                                        <i class="ki-outline ki-lock-2 fs-7 me-1"></i> PIN buka kunci: <b class="ms-1" style="letter-spacing:.12em">{{ $s->resume_pin ?? '—' }}</b>
                                    </span>
                                    <form action="{{ route('exam-sessions.pin', $s->id) }}" method="POST" class="d-inline">@csrf
                                        <button class="btn btn-sm btn-icon btn-light-primary" title="Buat ulang PIN"><i class="ki-outline ki-arrows-circle fs-6"></i></button>
                                    </form>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('exam-sessions.attempts', $s->id) }}" class="btn btn-sm btn-light-primary flex-grow-1"><i class="ki-outline ki-eye fs-5"></i> Peserta & Nilai</a>
                    @if($sStarted)
                                        <span class="btn btn-sm btn-icon btn-light disabled" title="Terkunci — ada peserta yang memulai"><i class="ki-outline ki-lock-2 fs-5"></i></span>
                                    @else
                                        @if($sCanEdit)
                                        <button class="btn btn-sm btn-icon btn-light-primary" data-bs-toggle="modal" data-bs-target="#editSession{{ $s->id }}" title="Edit sesi"><i class="ki-outline ki-pencil fs-5"></i></button>
                                        @endif
                                        <form action="{{ route('exam-sessions.toggle-active', $s->id) }}" method="POST">@csrf
                                            <button class="btn btn-sm btn-icon btn-light-{{ $s->is_active ? 'warning' : 'success' }}" title="{{ $s->is_active ? 'Nonaktifkan sesi' : 'Aktifkan sesi' }}">
                                                <i class="ki-outline ki-{{ $s->is_active ? 'eye-slash' : 'eye' }} fs-5"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('exam-sessions.destroy', $s->id) }}" method="POST" class="custom-ajax-confirm">@csrf @method('DELETE')<button class="btn btn-sm btn-icon btn-light-danger btn-delete"><i class="ki-outline ki-trash fs-5"></i></button></form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($sCanEdit)
                    {{-- Modal edit sesi (hanya bila belum dimulai & belum terlewat) --}}
                    <div class="modal fade drawer-modal" id="editSession{{ $s->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog"><div class="modal-content">
                            <form action="{{ route('exam-sessions.update', $s->id) }}" method="POST">
                                @csrf @method('PUT')
                                <div class="modal-header"><h3 class="modal-title">Edit Sesi</h3><div class="btn btn-icon btn-sm" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div></div>
                                <div class="modal-body px-8 py-6">
                                    <div class="alert alert-light-success fs-8 py-2">Peserta masih bisa diubah selama <b>belum ada yang memulai</b>. Untuk pindah kelas, ganti di <b>Pengaturan Ujian</b>.</div>
                                    <div class="mb-4"><label class="form-label required">Nama Sesi</label><input type="text" name="name" class="form-control" value="{{ $s->name }}" required></div>

                                    @php $sMode = $s->class_room_id ? 'class' : 'manual'; $sStudentIds = $s->students->pluck('id')->all(); @endphp
                                    <label class="form-label required d-block">Peserta <span class="text-muted fs-8">— kelas <b>{{ $examClass->name ?? '-' }}</b></span></label>
                                    <input type="hidden" name="class_room_id" value="{{ $examClass->id ?? '' }}">
                                    <div class="d-flex gap-4 mb-3 participant-toggle">
                                        <label class="form-check form-check-custom"><input class="form-check-input" type="radio" name="participant_mode" value="class" @checked($sMode==='class')> <span class="form-check-label ms-2">Seluruh kelas {{ $examClass->name ?? '' }}</span></label>
                                        <label class="form-check form-check-custom"><input class="form-check-input" type="radio" name="participant_mode" value="manual" @checked($sMode==='manual')> <span class="form-check-label ms-2">Pilih sebagian siswa</span></label>
                                    </div>
                                    <div class="by-class-wrap mb-4" style="{{ $sMode==='class' ? '' : 'display:none' }}">
                                        <div class="alert alert-light-primary py-2 mb-0 fs-8">Semua siswa kelas <b>{{ $examClass->name ?? '-' }}</b> ({{ $students->count() }} siswa) menjadi peserta.</div>
                                    </div>
                                    <div class="by-student-wrap mb-4" style="{{ $sMode==='manual' ? '' : 'display:none' }}">
                                        <select name="students[]" class="form-select" multiple size="6">
                                            @foreach($students as $st)<option value="{{ $st->id }}" @selected(in_array($st->id,$sStudentIds))>{{ $st->user->name ?? 'Siswa' }}</option>@endforeach
                                        </select>
                                        <span class="text-muted fs-8">Pilih sebagian siswa kelas {{ $examClass->name ?? '' }}. Tahan Ctrl/Cmd untuk memilih beberapa.</span>
                                    </div>

                                    <div class="row">
                                        {{-- flatpickr: pemilih tanggal + jam (picker bawaan browser tak punya jam) --}}
                                        <div class="col-md-6 mb-4"><label class="form-label required">Mulai</label><input type="text" name="starts_at" class="form-control js-datetime" value="{{ \Carbon\Carbon::parse($s->starts_at)->format('Y-m-d H:i') }}" placeholder="Pilih tanggal &amp; jam" autocomplete="off" required></div>
                                        <div class="col-md-6 mb-4"><label class="form-label required">Selesai</label><input type="text" name="ends_at" class="form-control js-datetime" value="{{ \Carbon\Carbon::parse($s->ends_at)->format('Y-m-d H:i') }}" placeholder="Pilih tanggal &amp; jam" autocomplete="off" required></div>
                                        <div class="col-md-6 mb-4"><label class="form-label required">Durasi (menit)</label><input type="number" name="duration_minutes" class="form-control" value="{{ $s->duration_minutes }}" required></div>
                                        <div class="col-md-6 mb-4"><label class="form-label">Kuota maks (kosong = ∞)</label><input type="number" name="max_capacity" class="form-control" value="{{ $s->max_capacity }}"></div>
                                    </div>
                                    <div class="d-flex flex-column gap-2">
                                        <label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="shuffle_questions" @checked($s->shuffle_questions)> <span class="ms-2">Acak urutan soal</span></label>
                                        <label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="shuffle_options" @checked($s->shuffle_options)> <span class="ms-2">Acak urutan opsi PG</span></label>
                                        <label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_result" @checked($s->show_result)> <span class="ms-2">Siswa boleh lihat nilai setelah selesai</span></label>
                                    </div>
                                </div>
                                <div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan Perubahan</button></div>
                            </form>
                        </div></div>
                    </div>
                    @endif
                    @empty
                    <div class="col-12"><div class="card"><div class="card-body text-center py-10 text-muted">Belum ada sesi. Buat sesi untuk menjadwalkan ujian.</div></div></div>
                    @endforelse
                </div>
            </div>

            {{-- ===== Modal Jadwalkan Susulan =====
                 Menambah SESI BARU pada ujian yang sama, khusus untuk siswa yang
                 belum tercatat mengikuti ujian. Sesi susulan tidak memakai
                 class_room_id, sehingga hanya siswa yang dicentang di sini yang
                 berhak masuk (lihat ExamPortalController::isEligible). Dikirim ke
                 jalur pembuatan sesi biasa dengan participant_mode=manual. --}}
            <div class="modal fade drawer-modal" id="makeupSessionModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog"><div class="modal-content">
                    <form action="{{ route('exam-sessions.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="exam_id" value="{{ $exam->id }}">
                        <input type="hidden" name="participant_mode" value="manual">
                        <div class="modal-header"><h3 class="modal-title">Jadwalkan Ujian Susulan</h3>
                            <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-1"></i></div>
                        </div>
                        <div class="modal-body px-8 py-6">
                            <div class="alert alert-light-warning fs-8 py-2 mb-5">
                                Daftar di bawah hanya memuat siswa yang <b>belum pernah tercatat mengikuti ujian ini</b>
                                di sesi mana pun. Siswa yang sudah mengerjakan tidak bisa dijadwalkan ulang.
                            </div>
                            <div class="row">
                                <div class="col-md-8 mb-4">
                                    <label class="form-label required">Nama sesi susulan</label>
                                    <input type="text" name="name" class="form-control form-control-solid"
                                        value="Susulan — {{ now()->format('d M Y') }}" required>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <label class="form-label required">Durasi (menit)</label>
                                    <input type="number" min="1" name="duration_minutes" class="form-control form-control-solid"
                                        value="{{ $exam->sessions->first()->duration_minutes ?? 60 }}" required>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label required">Mulai</label>
                                    <input type="datetime-local" name="starts_at" class="form-control form-control-solid" required>
                                </div>
                                <div class="col-md-6 mb-4">
                                    <label class="form-label required">Selesai</label>
                                    <input type="datetime-local" name="ends_at" class="form-control form-control-solid" required>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-4 mb-5">
                                <label class="form-check form-check-sm"><input class="form-check-input" type="checkbox" name="shuffle_questions" checked><span class="form-check-label fs-8 ms-2">Acak soal</span></label>
                                <label class="form-check form-check-sm"><input class="form-check-input" type="checkbox" name="shuffle_options" checked><span class="form-check-label fs-8 ms-2">Acak opsi</span></label>
                                <label class="form-check form-check-sm"><input class="form-check-input" type="checkbox" name="show_result"><span class="form-check-label fs-8 ms-2">Tampilkan hasil ke siswa</span></label>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0 required">Peserta susulan</label>
                                <label class="form-check form-check-sm">
                                    <input class="form-check-input" type="checkbox" id="makeupAll" checked>
                                    <span class="form-check-label fs-8 ms-2">Pilih semua</span>
                                </label>
                            </div>
                            <div style="max-height:38vh;overflow:auto">
                                @foreach($belumUjian as $s)
                                    <label class="d-flex align-items-center gap-3 border rounded p-3 mb-2">
                                        <input class="form-check-input makeup-item" type="checkbox" name="students[]" value="{{ $s->id }}" checked>
                                        <div>
                                            <div class="fw-semibold text-gray-900 fs-7">{{ $s->user->name ?? '-' }}</div>
                                            <div class="text-muted fs-8">{{ $s->nisn ?? 'tanpa NISN' }} · {{ $s->user->email ?? '-' }}</div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-warning">Buat Sesi Susulan</button>
                        </div>
                    </form>
                </div></div>
            </div>

            {{-- ================= TAB HASIL ================= --}}
            <div class="tab-pane fade" id="tab_hasil">
                <div class="card"><div class="card-body">
                    <table class="table align-middle table-row-dashed fs-6 gy-4">
                        <thead><tr class="text-gray-400 fw-bold fs-7 text-uppercase"><th>Sesi</th><th class="text-center">Mengerjakan</th><th class="text-center">Sudah Dinilai</th><th class="text-center">Perlu Periksa Essay</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            @forelse($exam->sessions as $s)
                            @php $att=$s->attempts; $graded=$att->where('status','graded')->count(); $pending=$att->where('status','submitted')->count(); @endphp
                            <tr>
                                <td class="fw-bold text-gray-900">{{ $s->name }}</td>
                                <td class="text-center">{{ $att->count() }}</td>
                                <td class="text-center"><span class="badge badge-light-success">{{ $graded }}</span></td>
                                <td class="text-center">@if($pending>0)<span class="badge badge-light-danger">{{ $pending }}</span>@else <span class="text-muted">0</span> @endif</td>
                                <td class="text-end"><a href="{{ route('exam-sessions.attempts', $s->id) }}" class="btn btn-sm btn-light-primary">Buka</a></td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-8 text-muted">Belum ada sesi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div></div>
            </div>
        </div>
    </div>
</div>

@include('backend.master.exams._modals')

@push('scripts')
<script>
    // ---- Susulan: pilih semua peserta ----
    (function(){
        var semua = document.getElementById('makeupAll');
        if (!semua) return;
        semua.addEventListener('change', function(){
            document.querySelectorAll('#makeupSessionModal .makeup-item').forEach(function(c){ c.checked = semua.checked; });
        });
    })();

    // ---- Atur Soal Aktif: tampilkan bagian yang relevan sesuai mode ----
    (function(){
        var modal = document.getElementById('selectionModal');
        if (!modal) return;
        function segarkan(){
            var mode = (modal.querySelector('.sel-mode:checked') || {}).value || 'all';
            modal.querySelector('#selCount').style.display = mode === 'auto' ? '' : 'none';
            modal.querySelector('#selList').style.display  = mode === 'manual' ? '' : 'none';
        }
        modal.querySelectorAll('.sel-mode').forEach(function(r){ r.addEventListener('change', segarkan); });
        var semua = modal.querySelector('#selAll');
        if (semua) semua.addEventListener('change', function(){
            modal.querySelectorAll('.sel-item').forEach(function(c){ c.checked = semua.checked; });
        });
        segarkan();
    })();

    // ---- Bank Soal: cari & pilih semua ----
    (function(){
        var search = document.getElementById('bankSearch');
        var checkAll = document.getElementById('bankCheckAll');
        if (search) search.addEventListener('input', function(){
            var kw = this.value.toLowerCase();
            document.querySelectorAll('#bankModal .bank-item').forEach(function(it){
                var t = (it.dataset.cari || it.querySelector('.bank-text')?.textContent || '').toLowerCase();
                it.style.display = t.indexOf(kw) !== -1 ? 'flex' : 'none';
            });
            // Kelompok ujian yang tidak punya soal cocok ikut disembunyikan.
            document.querySelectorAll('#bankModal .bank-grup').forEach(function(g){
                var terlihat = Array.prototype.filter.call(g.querySelectorAll('.bank-item'),
                    function(it){ return it.style.display !== 'none'; }).length;
                g.style.display = terlihat ? '' : 'none';
                var badge = g.querySelector('.bank-grup-jml');
                if (badge) badge.textContent = terlihat + ' soal';
            });
        });
        if (checkAll) checkAll.addEventListener('change', function(){
            document.querySelectorAll('#bankModal .bank-item').forEach(function(it){
                if (it.style.display === 'none') return;
                var c = it.querySelector('.bank-check'); if (c) c.checked = checkAll.checked;
            });
            document.querySelectorAll('#bankModal .bank-grup-all').forEach(function(g){ g.checked = checkAll.checked; });
        });

        // Centang kepala kelompok = pilih semua soal dari ujian itu (yang sedang terlihat).
        document.querySelectorAll('#bankModal .bank-grup-all').forEach(function(head){
            head.addEventListener('change', function(){
                head.closest('.bank-grup').querySelectorAll('.bank-item').forEach(function(it){
                    if (it.style.display === 'none') return;
                    var c = it.querySelector('.bank-check'); if (c) c.checked = head.checked;
                });
            });
        });
    })();

    // ---- Dynamic MC option rows (untuk semua container .mc-options) ----
    function renumber(container){
        container.querySelectorAll('.mc-row').forEach((row, idx) => {
            row.querySelector('input[type=radio]').value = idx;
        });
    }
    document.querySelectorAll('.mc-options').forEach(c => renumber(c));
    document.addEventListener('click', function(e){
        if (e.target.closest('.mc-add')) {
            const wrap = e.target.closest('.modal-body').querySelector('.mc-options');
            const row = document.createElement('div');
            row.className = 'mc-row border border-gray-300 rounded p-3 mb-2';
            row.innerHTML = '<div class="d-flex align-items-start gap-3">' +
                '<span class="pt-2"><input class="form-check-input mt-0" type="radio" name="correct" title="Tandai sebagai kunci jawaban"></span>' +
                '<div class="flex-grow-1">' +
                    '<input type="hidden" name="option_ids[]" value="">' +
                    '<input type="text" name="options[]" class="form-control math-input mb-2" placeholder="Teks opsi (boleh $rumus$, boleh dikosongkan bila pakai gambar)">' +
                    '<input type="file" name="option_images[]" class="form-control form-control-sm" accept="image/*">' +
                '</div>' +
                '<button type="button" class="btn btn-icon btn-light-danger mc-remove" title="Hapus opsi"><i class="ki-outline ki-trash fs-6"></i></button>' +
            '</div>';
            wrap.appendChild(row); renumber(wrap);
        }
        if (e.target.closest('.mc-remove')) {
            const wrap = e.target.closest('.mc-options');
            const rows = wrap.querySelectorAll('.mc-row');
            if (rows.length > 2) { e.target.closest('.mc-row').remove(); renumber(wrap); }
            else { Swal.fire('Info','Minimal 2 opsi.','info'); }
        }
    });

    // ---- Konfirmasi hapus (form .custom-ajax-confirm) ----
    document.querySelectorAll('.custom-ajax-confirm .btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e){
            e.preventDefault();
            const form = this.closest('form');
            Swal.fire({title:'Yakin hapus?', icon:'warning', showCancelButton:true, confirmButtonText:'Ya', cancelButtonText:'Batal', confirmButtonColor:'#d33'})
              .then(r => { if(r.isConfirmed) form.submit(); });
        });
    });

    // ---- Pemilih tanggal + JAM untuk jadwal sesi (flatpickr, sudah ada di plugins.bundle) ----
    // Nilai yang dikirim tetap "Y-m-d H:i" (diterima validasi `date` di server).
    (function(){
        var els = document.querySelectorAll('.js-datetime');
        if (!els.length) return;
        if (typeof flatpickr === 'undefined'){
            // Plugin tidak tersedia → kembalikan ke input datetime bawaan browser agar tetap bisa dipakai.
            els.forEach(function(i){ i.type = 'datetime-local'; i.value = (i.value || '').replace(' ', 'T'); });
            return;
        }
        els.forEach(function(i){
            flatpickr(i, {
                enableTime: true,
                time_24hr: true,
                dateFormat: 'Y-m-d H:i',     // format yang dikirim ke server
                altInput: true,
                altFormat: 'd/m/Y H:i',      // yang dilihat pengguna
                minuteIncrement: 5,
                allowInput: true,
                static: true,                // penting: agar kalender ikut di dalam modal/drawer
            });
            // flatpickr menyembunyikan input asli; pindahkan atribut required ke input tampak
            // supaya validasi "wajib diisi" di browser tetap berjalan.
            if (i._flatpickr && i._flatpickr.altInput && i.required) i._flatpickr.altInput.required = true;
        });
    })();

    // ---- Pengaturan: konfirmasi bila ganti kategori menghapus soal yang tak sesuai ----
    const editExamForm = document.getElementById('editExamForm');
    if (editExamForm) {
        editExamForm.addEventListener('submit', function(e){
            const sel = document.getElementById('examTypeSelect');
            if (!sel) return;
            const type = sel.value;
            const mc = parseInt(sel.dataset.mcCount || '0', 10);
            const essay = parseInt(sel.dataset.essayCount || '0', 10);
            let label = '';
            if (type === 'mc' && essay > 0) label = essay + ' soal Essay';
            else if (type === 'essay' && mc > 0) label = mc + ' soal Pilihan Ganda';
            if (label) {
                e.preventDefault();
                Swal.fire({
                    title: 'Ubah kategori ujian?',
                    html: 'Mengubah kategori akan <b>menghapus ' + label + '</b> yang sudah dibuat dan tidak bisa dikembalikan. Lanjutkan?',
                    icon: 'warning', showCancelButton: true,
                    confirmButtonText: 'Ya, ubah & hapus', cancelButtonText: 'Batal', confirmButtonColor: '#d33'
                }).then(r => { if (r.isConfirmed) editExamForm.submit(); });
            }
        });
    }

    // ---- Sesi: toggle mode peserta (kelas vs manual) — berlaku per modal (buat & edit) ----
    document.querySelectorAll('.participant-toggle input[name=participant_mode]').forEach(r => {
        r.addEventListener('change', function(){
            const scope = this.closest('.modal-body') || document;
            const cls = scope.querySelector('.by-class-wrap');
            const stu = scope.querySelector('.by-student-wrap');
            if (cls) cls.style.display = this.value === 'class' ? 'block' : 'none';
            if (stu) stu.style.display = this.value === 'manual' ? 'block' : 'none';
        });
    });
</script>
@include('partials.img-zoom', ['sel' => 'img.zoomable'])
@endpush
@endsection
