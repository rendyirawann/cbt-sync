{{-- ===== Edit Pengaturan Ujian =====
     Hak ubah dihitung di App\Support\SiklusUjian::bolehUbahPengaturan():
     terkunci total begitu ada yang memulai, dan sesudah ujian terbit hanya
     Superadmin/Developer. Tombol simpan di bawah ikut dinonaktifkan, TAPI yang
     benar-benar menjaga adalah pemeriksaan di ExamController::update() —
     tombol nonaktif saja bisa dilewati dengan mengirim POST langsung. --}}
@php
    $bolehUbahSet = \App\Support\SiklusUjian::bolehUbahPengaturan($exam);
    $alasanSet = \App\Support\SiklusUjian::alasanPengaturanTerkunci($exam);
@endphp
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
                        <select name="points_mode" id="eModeSel" class="form-select">
                            <option value="auto" @selected($exam->points_mode==='auto')>Otomatis — poin dibagi rata sistem</option>
                            <option value="manual" @selected($exam->points_mode==='manual')>Manual — poin tiap soal diisi guru</option>
                        </select>
                        <div class="form-text">Poin soal dihitung sistem (tiap bagian bertotal 100). Bedanya saat memeriksa essay:
                            <b>Otomatis</b> = tandai Benar/Salah, <b>Manual</b> = guru mengisi nilai tiap essay (total maks 100).
                            Nilai akhir = (Nilai PG + Nilai Essay) ÷ 2.</div>
                    </div>
                    <div class="col-md-6 mb-4"><label class="form-label">KKM</label><input type="number" step="0.01" name="pass_score" class="form-control" value="{{ rtrim(rtrim((string)$exam->pass_score,'0'),'.') }}"></div>
                </div>
                <div class="text-muted fs-7"><i class="ki-outline ki-information-5 fs-6 text-primary me-1"></i> Nilai akhir selalu berskala 0–100 (rata-rata nilai PG dan nilai Essay).</div>
            </div>
            <div class="modal-footer">
                @if($alasanSet)<div class="text-danger fs-8 me-auto">{{ $alasanSet }}</div>@endif
                <button type="submit" class="btn btn-primary" @disabled(!$bolehUbahSet)>Simpan</button>
            </div>
        </form>
    </div></div>
</div>

{{-- ===== Tambah Soal Pilihan Ganda ===== --}}
@if($exam->hasMc())
<div class="modal fade drawer-modal" id="addMcModal" tabindex="-1" data-bs-focus="false" aria-hidden="true">
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
                <div class="alert alert-light-primary py-3 mb-4 fs-7">
                    <i class="ki-outline ki-information-5 fs-4 text-primary me-1"></i>
                    Poin tidak perlu diisi — sistem membagi rata otomatis: <b>100 ÷ jumlah soal PG</b>.
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
<div class="modal fade drawer-modal" id="addEssayModal" tabindex="-1" data-bs-focus="false" aria-hidden="true">
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
                @if($exam->points_mode === 'auto')
                    <div class="alert alert-light-info py-3 mb-4 fs-7">
                        <i class="ki-outline ki-information-5 fs-4 text-info me-1"></i>
                        Mode <b>Otomatis</b>: bobot dihitung sistem, <b>100 ÷ jumlah soal essay</b>.
                        Saat memeriksa, guru cukup menandai Benar/Salah.
                    </div>
                @else
                    {{-- Mode MANUAL: bobot diisi di sini, saat soal dibuat. Permintaan sekolah —
                         waktu guru mengoreksi, nilai maksimal tiap soal sudah tertera dan tidak
                         perlu ditentukan ulang. Sisa jatah ditampilkan supaya total tetap 100. --}}
                    @php $sisaBobot = $exam->sisaBobotEssay(); $fB = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.'); @endphp
                    <div class="mb-4">
                        <label class="form-label required">Bobot / Nilai maksimal soal ini</label>
                        <div class="input-group">
                            <input type="number" name="points" class="form-control" step="0.01" min="0.01" max="100"
                                   value="{{ $fB(max($sisaBobot, 0)) }}" required>
                            <span class="input-group-text">poin</span>
                        </div>
                        <div class="form-text">
                            Mode <b>Manual</b>: total bobot seluruh soal essay <b>100 poin</b>.
                            Terpakai <b>{{ $fB($exam->totalBobotEssay()) }}</b>, sisa <b class="{{ $sisaBobot > 0 ? 'text-primary' : 'text-danger' }}">{{ $fB($sisaBobot) }}</b> poin.
                            Nilai ini yang muncul sebagai batas maksimal saat memeriksa jawaban.
                        </div>
                    </div>
                @endif

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
            <div class="modal-header"><h3 class="modal-title">Buat Jadwal Ujian</h3><div class="btn btn-icon btn-sm" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div></div>
            <div class="modal-body px-8 py-6">
                <div class="mb-4"><label class="form-label required">Nama Jadwal</label><input type="text" name="name" class="form-control" placeholder="cth: Asesmen Nasional 2026" value="{{ $exam->title }}" required></div>

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
                    @include('backend.master._pilih-siswa', ['uid' => 'psBuat'])
                    <span class="text-muted fs-8 d-block mt-2">Centang siswa kelas {{ $examClass->name ?? '' }} yang ikut jadwal ini.
                        <b>Pilih semua</b>/<b>Kosongkan</b> berlaku pada daftar yang sedang tampil, dan klik sambil menahan <b>Shift</b> mencentang satu rentang sekaligus.</span>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4"><label class="form-label required">Tanggal Mulai</label><input type="date" name="starts_at" class="form-control" required></div>
                    <div class="col-md-6 mb-4"><label class="form-label required">Tanggal Selesai</label><input type="date" name="ends_at" class="form-control" required></div>
                    <div class="col-12 mb-4">
                        <div class="alert alert-light-primary py-2 mb-0 fs-8">
                            Jadwal hanya berupa <b>rentang tanggal</b> — tidak ada jam mulai/selesai. Siswa boleh masuk
                            kapan saja selama rentang itu, dan guru penanggung jawab yang mengatur pelaksanaannya.
                            Jam pelaksanaan per gelombang diatur di <b>Data Master &rarr; Master Gelombang</b> dan
                            dipakai untuk kolom PUKUL pada lembar Daftar Hadir.
                        </div>
                    </div>
                    <div class="col-md-6 mb-4"><label class="form-label required">Durasi pengerjaan (menit)</label><input type="number" name="duration_minutes" class="form-control" value="120" min="1" required>
                        <div class="form-text">Lama waktu tiap siswa mengerjakan, dihitung sejak ia menekan Mulai Ujian.</div>
                    </div>
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
