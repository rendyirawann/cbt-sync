{{-- ===== Edit Pengaturan Ujian ===== --}}
<div class="modal fade drawer-modal" id="editExamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <form action="{{ route('exams.update', $exam->id) }}" method="POST" id="editExamForm">
            @csrf @method('PUT')
            <div class="modal-header"><h3 class="modal-title">Pengaturan Ujian</h3><div class="btn btn-icon btn-sm" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div></div>
            <div class="modal-body px-8 py-6">
                <div class="mb-4">
                    <label class="form-label required">Mata Pelajaran / Kelas (Penugasan)</label>
                    <select name="teaching_assignment_id" class="form-select">
                        @foreach($assignments as $ta)
                        <option value="{{ $ta->id }}" @selected($exam->teaching_assignment_id === $ta->id)>{{ $ta->subject->name ?? '-' }} — {{ $ta->classRoom->name ?? '-' }}@if(!auth()->user()->hasRole('Guru')) ({{ $ta->teacher->user->name ?? '-' }})@endif</option>
                        @endforeach
                    </select>
                    <div class="form-text">Mengganti ini memindahkan ujian & sesinya ke kelas/mapel tersebut. Hanya bisa selama belum ada peserta yang memulai.</div>
                </div>
                <div class="mb-4"><label class="form-label required">Judul</label><input type="text" name="title" class="form-control" value="{{ $exam->title }}" required></div>
                <div class="mb-4"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="2">{{ $exam->description }}</textarea></div>
                <div class="row">
                    <div class="col-md-6 mb-4"><label class="form-label required">Kategori</label>
                        <select name="type" class="form-select" id="examTypeSelect"
                            data-mc-count="{{ $exam->questions->where('type','mc')->count() }}"
                            data-essay-count="{{ $exam->questions->where('type','essay')->count() }}">
                            <option value="mixed" @selected($exam->type==='mixed')>PG + Essay</option>
                            <option value="mc" @selected($exam->type==='mc')>Pilihan Ganda saja</option>
                            <option value="essay" @selected($exam->type==='essay')>Essay saja</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-4"><label class="form-label required">Mode Penilaian</label>
                        <select name="points_mode" class="form-select">
                            <option value="per_question" @selected($exam->points_mode==='per_question')>Per soal</option>
                            <option value="equal" @selected($exam->points_mode==='equal')>Bagi rata otomatis</option>
                            <option value="manual" @selected($exam->points_mode==='manual')>Manual</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-4"><label class="form-label">Pengurang salah (bagi rata)</label><input type="number" step="0.01" name="wrong_penalty" class="form-control" value="{{ rtrim(rtrim((string)$exam->wrong_penalty,'0'),'.') }}"></div>
                    <div class="col-md-6 mb-4"><label class="form-label">KKM</label><input type="number" step="0.01" name="pass_score" class="form-control" value="{{ rtrim(rtrim((string)$exam->pass_score,'0'),'.') }}"></div>
                </div>
                <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="normalize" id="enorm" @checked($exam->normalize)><label class="form-check-label" for="enorm">Normalisasi nilai ke 0–100</label></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan</button></div>
        </form>
    </div></div>
</div>

{{-- ===== Tambah Soal Pilihan Ganda ===== --}}
@if($exam->hasMc())
<div class="modal fade drawer-modal drawer-wide" id="addMcModal" tabindex="-1" data-bs-focus="false" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <form action="{{ route('exam-questions.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="exam_id" value="{{ $exam->id }}">
            <input type="hidden" name="type" value="mc">
            <div class="modal-header"><h3 class="modal-title">Tambah Soal Pilihan Ganda</h3><div class="btn btn-icon btn-sm" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div></div>
            <div class="modal-body px-8 py-6 rdev-math-scope">
                <div class="mb-4">
                    <label class="form-label required">Pertanyaan <span class="text-muted fs-8">(rumus: tulis di antara $ … $)</span></label>
                    @include('partials.math-toolbar')
                    <textarea name="question_text" class="form-control math-input" data-preview="#prev_addmc" rows="3" required></textarea>
                    <div class="math-preview" id="prev_addmc"></div>
                </div>
                <div class="row">
                    <div class="col-6 mb-4"><label class="form-label required">Poin bila benar</label><input type="number" step="0.01" name="points" class="form-control" value="1" required></div>
                    <div class="col-6 mb-4"><label class="form-label">Pengurang bila salah</label><input type="number" step="0.01" name="penalty" class="form-control" value="0"></div>
                </div>
                <div class="mb-4"><label class="form-label">Gambar Soal (opsional)</label><input type="file" name="image" class="form-control" accept="image/*"><div class="form-text">Format JPG/JPEG/PNG, maksimal 3 MB. Cocok untuk diagram/grafik/gambar soal.</div></div>
                <label class="form-label required">Opsi Jawaban <span class="text-muted fs-8">(klik bulatan = kunci jawaban • tiap opsi boleh teks, rumus $…$, dan/atau gambar)</span></label>
                <div class="mc-options">
                    @for($k=0;$k<4;$k++)
                    <div class="mc-row border border-gray-300 rounded p-3 mb-2">
                        <div class="d-flex align-items-start gap-3">
                            <span class="pt-2"><input class="form-check-input mt-0" type="radio" name="correct" value="{{ $k }}" title="Tandai sebagai kunci jawaban" {{ $k===0 ? 'checked':'' }}></span>
                            <div class="flex-grow-1">
                                <input type="hidden" name="option_ids[]" value="">
                                <input type="text" name="options[]" class="form-control math-input mb-2" placeholder="Teks opsi {{ chr(65+$k) }} (boleh $rumus$, boleh dikosongkan bila pakai gambar)">
                                <input type="file" name="option_images[]" class="form-control form-control-sm" accept="image/*">
                            </div>
                            <button type="button" class="btn btn-icon btn-light-danger mc-remove" title="Hapus opsi"><i class="ki-outline ki-trash fs-6"></i></button>
                        </div>
                    </div>
                    @endfor
                </div>
                <button type="button" class="btn btn-sm btn-light-primary mc-add"><i class="ki-outline ki-plus fs-6"></i> Tambah opsi</button>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan Soal</button></div>
        </form>
    </div></div>
</div>
@endif

{{-- ===== Tambah Soal Essay ===== --}}
@if($exam->hasEssay())
<div class="modal fade drawer-modal drawer-wide" id="addEssayModal" tabindex="-1" data-bs-focus="false" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <form action="{{ route('exam-questions.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="exam_id" value="{{ $exam->id }}">
            <input type="hidden" name="type" value="essay">
            <div class="modal-header"><h3 class="modal-title">Tambah Soal Essay</h3><div class="btn btn-icon btn-sm" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div></div>
            <div class="modal-body px-8 py-6 rdev-math-scope">
                <div class="mb-4">
                    <label class="form-label required">Pertanyaan <span class="text-muted fs-8">(rumus: tulis di antara $ … $)</span></label>
                    @include('partials.math-toolbar')
                    <textarea name="question_text" class="form-control math-input" data-preview="#prev_addessay" rows="4" required></textarea>
                    <div class="math-preview" id="prev_addessay"></div>
                </div>
                <div class="mb-4"><label class="form-label required">Skor Maksimal</label><input type="number" step="0.01" name="points" class="form-control" value="10" required></div>
                <div class="mb-4"><label class="form-label">Gambar (opsional)</label><input type="file" name="image" class="form-control" accept="image/*"><div class="form-text">Format JPG/JPEG/PNG, maksimal 3 MB. Cocok untuk diagram/grafik/gambar soal.</div></div>
                <div class="alert alert-light-info fs-7">Jawaban essay dinilai manual oleh guru di menu "Peserta & Nilai".</div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-info text-white">Simpan Soal</button></div>
        </form>
    </div></div>
</div>
@endif

{{-- ===== Buat Sesi Ujian ===== --}}
<div class="modal fade drawer-modal" id="addSessionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <form action="{{ route('exam-sessions.store') }}" method="POST">
            @csrf
            <input type="hidden" name="exam_id" value="{{ $exam->id }}">
            <div class="modal-header"><h3 class="modal-title">Buat Sesi Ujian</h3><div class="btn btn-icon btn-sm" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div></div>
            <div class="modal-body px-8 py-6">
                <div class="mb-4"><label class="form-label required">Nama Sesi</label><input type="text" name="name" class="form-control" placeholder="cth: Sesi 1 — Pagi" required></div>

                <label class="form-label required d-block">Peserta <span class="text-muted fs-8">— ujian ini untuk kelas <b>{{ $examClass->name ?? '-' }}</b></span></label>
                <input type="hidden" name="class_room_id" value="{{ $examClass->id ?? '' }}">
                <div class="d-flex gap-4 mb-3 participant-toggle">
                    <label class="form-check form-check-custom"><input class="form-check-input" type="radio" name="participant_mode" value="class" checked> <span class="form-check-label ms-2">Seluruh kelas {{ $examClass->name ?? '' }}</span></label>
                    <label class="form-check form-check-custom"><input class="form-check-input" type="radio" name="participant_mode" value="manual"> <span class="form-check-label ms-2">Pilih sebagian siswa</span></label>
                </div>
                <div class="by-class-wrap mb-4">
                    <div class="alert alert-light-primary py-2 mb-0 fs-8">Semua siswa kelas <b>{{ $examClass->name ?? '-' }}</b> ({{ $students->count() }} siswa) otomatis menjadi peserta.</div>
                </div>
                <div class="by-student-wrap mb-4" style="display:none">
                    <select name="students[]" class="form-select" multiple size="6">
                        @foreach($students as $st)<option value="{{ $st->id }}">{{ $st->user->name ?? 'Siswa' }}</option>@endforeach
                    </select>
                    <span class="text-muted fs-8">Pilih sebagian siswa kelas {{ $examClass->name ?? '' }} (mis. bagi sesi pagi/siang atau remedial). Tahan Ctrl/Cmd untuk memilih beberapa.</span>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4"><label class="form-label required">Mulai</label><input type="datetime-local" name="starts_at" class="form-control" required></div>
                    <div class="col-md-6 mb-4"><label class="form-label required">Selesai</label><input type="datetime-local" name="ends_at" class="form-control" required></div>
                    <div class="col-md-6 mb-4"><label class="form-label required">Durasi (menit)</label><input type="number" name="duration_minutes" class="form-control" value="60" required></div>
                    <div class="col-md-6 mb-4"><label class="form-label">Kuota maks (kosong = ∞)</label><input type="number" name="max_capacity" class="form-control" placeholder="cth: 40"></div>
                </div>
                <div class="d-flex flex-column gap-2">
                    <label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="shuffle_questions" checked> <span class="ms-2">Acak urutan soal</span></label>
                    <label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="shuffle_options" checked> <span class="ms-2">Acak urutan opsi PG</span></label>
                    <label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_result" checked> <span class="ms-2">Siswa boleh lihat nilai setelah selesai</span></label>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Buat Sesi</button></div>
        </form>
    </div></div>
</div>
