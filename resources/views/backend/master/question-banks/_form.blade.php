@php
    $isEdit = ($mode ?? 'add') === 'edit';
    $modalId = $isEdit ? 'editBank'.$bank->id : 'addBankModal';
    $action = $isEdit ? route('question-banks.update', $bank->id) : route('question-banks.store');
    $uid = $isEdit ? $bank->id : 'new';
@endphp
<div class="modal fade drawer-modal drawer-wide" id="{{ $modalId }}" tabindex="-1" data-bs-focus="false" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <form action="{{ $action }}" method="POST" enctype="multipart/form-data" class="bank-form">
            @csrf @if($isEdit)@method('PUT')@endif
            <div class="modal-header"><h3 class="modal-title">{{ $isEdit ? 'Edit' : 'Tambah' }} Soal Bank</h3><div class="btn btn-icon btn-sm" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div></div>
            <div class="modal-body px-8 py-6 rdev-math-scope">
                <div class="row">
                    <div class="col-md-6 mb-4"><label class="form-label required">Mata Pelajaran</label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">Pilih mapel...</option>
                            @foreach($subjects as $s)<option value="{{ $s->id }}" @selected($isEdit && $bank->subject_id===$s->id)>{{ $s->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-4"><label class="form-label">Tingkat (opsional)</label><input type="text" name="level" class="form-control" value="{{ $isEdit ? $bank->level : '' }}" placeholder="mis. 10"></div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-4"><label class="form-label required">Tipe</label>
                        @if($isEdit)
                            <input type="hidden" name="type" value="{{ $bank->type }}">
                            <input type="text" class="form-control" value="{{ $bank->type==='mc'?'Pilihan Ganda':'Essay' }}" disabled>
                        @else
                            <select name="type" class="form-select">
                                <option value="mc">Pilihan Ganda</option>
                                <option value="essay">Essay</option>
                            </select>
                        @endif
                    </div>
                    <div class="col-md-4 mb-4"><label class="form-label required">Poin</label><input type="number" step="0.01" name="points" class="form-control" value="{{ $isEdit ? rtrim(rtrim((string)$bank->points,'0'),'.') : 1 }}" required></div>
                    <div class="col-md-4 mb-4"><label class="form-label">Pengurang (salah)</label><input type="number" step="0.01" name="penalty" class="form-control" value="{{ $isEdit ? rtrim(rtrim((string)$bank->penalty,'0'),'.') : 0 }}"></div>
                </div>
                <div class="mb-4">
                    <label class="form-label required">Pertanyaan <span class="text-muted fs-8">(rumus: $ … $)</span></label>
                    @include('partials.math-toolbar')
                    <textarea name="question_text" class="form-control math-input" data-preview="#prevbank_{{ $uid }}" rows="3" required>{{ $isEdit ? $bank->question_text : '' }}</textarea>
                    <div class="math-preview" id="prevbank_{{ $uid }}"></div>
                </div>
                @if($isEdit && $bank->image_path)<div class="mb-2"><img src="{{ asset('storage/'.$bank->image_path) }}" class="rounded mh-100px"></div>@endif
                <div class="mb-4"><label class="form-label">Gambar Soal (opsional)</label><input type="file" name="image" class="form-control" accept="image/*"><div class="form-text">JPG/JPEG/PNG, maks 3 MB.</div></div>

                <div class="mc-section" @if($isEdit && $bank->type!=='mc') style="display:none" @endif>
                    <label class="form-label required">Opsi Jawaban <span class="text-muted fs-8">(klik bulatan = kunci • boleh teks/rumus/gambar)</span></label>
                    <div class="mc-options">
                        @if($isEdit && $bank->type==='mc')
                            @foreach($bank->options as $oi => $opt)
                            <div class="mc-row border border-gray-300 rounded p-3 mb-2">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="pt-2"><input class="form-check-input mt-0" type="radio" name="correct" value="{{ $oi }}" {{ $opt->is_correct?'checked':'' }}></span>
                                    <div class="flex-grow-1">
                                        <input type="hidden" name="option_ids[]" value="{{ $opt->id }}">
                                        <input type="text" name="options[]" class="form-control math-input mb-2" value="{{ $opt->option_text }}" placeholder="Teks opsi (boleh $rumus$/gambar)">
                                        @if($opt->image_path)
                                        <div class="d-flex align-items-center gap-3 mb-2">
                                            <img src="{{ asset('storage/'.$opt->image_path) }}" class="rounded mh-60px">
                                            <label class="form-check form-check-sm d-flex align-items-center gap-2 mb-0"><input class="form-check-input" type="checkbox" name="option_remove_image[]" value="{{ $opt->id }}"><span class="fs-8 text-muted">Hapus gambar</span></label>
                                        </div>
                                        @endif
                                        <input type="file" name="option_images[]" class="form-control form-control-sm" accept="image/*">
                                    </div>
                                    <button type="button" class="btn btn-icon btn-light-danger mc-remove"><i class="ki-outline ki-trash fs-6"></i></button>
                                </div>
                            </div>
                            @endforeach
                        @else
                            @for($k=0;$k<4;$k++)
                            <div class="mc-row border border-gray-300 rounded p-3 mb-2">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="pt-2"><input class="form-check-input mt-0" type="radio" name="correct" value="{{ $k }}" {{ $k===0?'checked':'' }}></span>
                                    <div class="flex-grow-1">
                                        <input type="hidden" name="option_ids[]" value="">
                                        <input type="text" name="options[]" class="form-control math-input mb-2" placeholder="Teks opsi {{ chr(65+$k) }} (boleh $rumus$/gambar)">
                                        <input type="file" name="option_images[]" class="form-control form-control-sm" accept="image/*">
                                    </div>
                                    <button type="button" class="btn btn-icon btn-light-danger mc-remove"><i class="ki-outline ki-trash fs-6"></i></button>
                                </div>
                            </div>
                            @endfor
                        @endif
                    </div>
                    <button type="button" class="btn btn-sm btn-light-primary mc-add"><i class="ki-outline ki-plus fs-6"></i> Tambah opsi</button>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Simpan</button></div>
        </form>
    </div></div>
</div>
