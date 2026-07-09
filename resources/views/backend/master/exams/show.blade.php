@extends('backend.layout.app')
@section('title', 'Kelola Ujian: ' . $exam->title)

@section('content')
@php
    $typeLabel = ['mixed'=>'PG + Essay','mc'=>'Pilihan Ganda','essay'=>'Essay'][$exam->type] ?? $exam->type;
    $modeLabel = ['per_question'=>'Per soal','equal'=>'Bagi rata otomatis','manual'=>'Manual'][$exam->points_mode] ?? $exam->points_mode;
    // Ujian terkunci begitu ada minimal 1 siswa yang memulai (attempt). Soal & publish dikunci.
    $locked = $exam->hasStartedAttempts();
@endphp

@include('partials.katex')

<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0">{{ $exam->title }}</h1>
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
                <div class="d-flex gap-2 mb-5">
                    @if($locked)
                        <span class="text-muted fs-7"><i class="ki-outline ki-lock-2 fs-5 text-primary me-1"></i> Soal terkunci — sudah ada peserta yang memulai.</span>
                    @else
                        @if($exam->hasMc())
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMcModal"><i class="ki-outline ki-plus fs-4"></i> Tambah Pilihan Ganda</button>
                        @endif
                        @if($exam->hasEssay())
                        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#addEssayModal"><i class="ki-outline ki-plus fs-4"></i> Tambah Essay</button>
                        @endif
                    @endif
                </div>

                @forelse($exam->questions as $i => $q)
                <div class="card mb-4">
                    <div class="card-body d-flex">
                        <div class="me-4"><span class="badge badge-circle badge-primary fs-6">{{ $i + 1 }}</span></div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between">
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
                            @if($q->image_path)<img src="{{ asset('storage/'.$q->image_path) }}" class="rounded mb-3 mh-150px">@endif
                            @if($q->type === 'mc')
                                <div class="d-flex flex-column gap-1">
                                    @foreach($q->options as $opt)
                                    <div class="d-flex align-items-center {{ $opt->is_correct ? 'text-success fw-bold' : 'text-gray-700' }}">
                                        <span class="badge badge-{{ $opt->is_correct ? 'success' : 'secondary' }} me-2">{{ $opt->label }}</span>
                                        {{ $opt->option_text }}
                                        @if($opt->is_correct)<i class="ki-outline ki-check-circle fs-4 text-success ms-2"></i>@endif
                                    </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- edit modal per soal --}}
                <div class="modal fade drawer-modal" id="editQ{{ $q->id }}" tabindex="-1" aria-hidden="true">
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
                                        <div class="col-6 mb-4"><label class="form-label required">Poin (benar)</label><input type="number" step="0.01" name="points" class="form-control" value="{{ rtrim(rtrim((string)$q->points,'0'),'.') }}" required></div>
                                        @if($q->type === 'mc')<div class="col-6 mb-4"><label class="form-label">Pengurang (salah)</label><input type="number" step="0.01" name="penalty" class="form-control" value="{{ rtrim(rtrim((string)$q->penalty,'0'),'.') }}"></div>@endif
                                    </div>
                                    <div class="mb-4"><label class="form-label">Ganti Gambar (opsional)</label><input type="file" name="image" class="form-control" accept="image/*"><div class="form-text">Format JPG/JPEG/PNG, maksimal 5 MB.</div></div>
                                    @if($q->type === 'mc')
                                    <label class="form-label required">Opsi Jawaban (pilih kunci)</label>
                                    <div class="mc-options">
                                        @foreach($q->options as $oi => $opt)
                                        <div class="input-group mb-2 mc-row">
                                            <span class="input-group-text"><input class="form-check-input mt-0" type="radio" name="correct" value="{{ $oi }}" {{ $opt->is_correct ? 'checked' : '' }}></span>
                                            <input type="text" name="options[]" class="form-control math-input" value="{{ $opt->option_text }}" required>
                                            <button type="button" class="btn btn-light-danger mc-remove"><i class="ki-outline ki-trash fs-6"></i></button>
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
                                    <div class="alert alert-light-info fs-8 py-2">Peserta sesi tidak dapat diubah di sini. Untuk mengganti kelas/daftar siswa, hapus sesi lalu buat baru.</div>
                                    <div class="mb-4"><label class="form-label required">Nama Sesi</label><input type="text" name="name" class="form-control" value="{{ $s->name }}" required></div>
                                    <div class="row">
                                        <div class="col-md-6 mb-4"><label class="form-label required">Mulai</label><input type="datetime-local" name="starts_at" class="form-control" value="{{ \Carbon\Carbon::parse($s->starts_at)->format('Y-m-d\TH:i') }}" required></div>
                                        <div class="col-md-6 mb-4"><label class="form-label required">Selesai</label><input type="datetime-local" name="ends_at" class="form-control" value="{{ \Carbon\Carbon::parse($s->ends_at)->format('Y-m-d\TH:i') }}" required></div>
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
            row.className = 'input-group mb-2 mc-row';
            row.innerHTML = '<span class="input-group-text"><input class="form-check-input mt-0" type="radio" name="correct"></span>' +
                '<input type="text" name="options[]" class="form-control math-input" placeholder="Teks opsi (boleh $rumus$)" required>' +
                '<button type="button" class="btn btn-light-danger mc-remove"><i class="ki-outline ki-trash fs-6"></i></button>';
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

    // ---- Sesi: toggle mode peserta (kelas vs manual) ----
    document.querySelectorAll('input[name=participant_mode]').forEach(r => {
        r.addEventListener('change', function(){
            document.getElementById('byClassWrap').style.display = this.value === 'class' ? 'block' : 'none';
            document.getElementById('byStudentWrap').style.display = this.value === 'manual' ? 'block' : 'none';
        });
    });
</script>
@endpush
@endsection
