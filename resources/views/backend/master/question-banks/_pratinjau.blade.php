{{-- Pratinjau satu soal Bank (baca saja), dimuat lewat AJAX oleh tombol "Lihat".

     Sebelumnya modal ini dirender untuk SETIAP soal di halaman daftar. Bersama
     form ubah, itu membuat HTML halaman Bank Soal 761 KB hanya untuk 19 soal —
     dan bank soal memang dirancang tumbuh terus. --}}
        {{-- Pratinjau soal bank (baca saja) — dipakai tombol "Lihat". --}}
        <div class="modal fade" id="isiPratinjauBank" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered mw-650px"><div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Pratinjau Soal Bank</h3>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i></div>
                </div>
                <div class="modal-body">
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <span class="badge badge-light-{{ $bank->type==='mc'?'primary':'info' }}">{{ $bank->type==='mc'?'Pilihan Ganda':'Essay' }}</span>
                        <span class="badge badge-light-success">{{ $bank->subject->name ?? '-' }}</span>
                        @if($bank->level)<span class="badge badge-light-warning">Tingkat {{ $bank->level }}</span>@endif
                        <span class="badge badge-light-dark">{{ $bank->school->name ?? 'Tanpa sekolah' }}</span>
                        @if($bank->sourceSchool)
                            <span class="badge badge-light text-muted">sumber: {{ $bank->sourceSchool->name }}</span>
                        @endif
                    </div>
                    <div class="fw-semibold text-gray-900 mb-3">{!! nl2br(e($bank->question_text)) !!}</div>
                    @if($bank->image_path)
                        <img src="{{ asset('storage/'.$bank->image_path) }}" class="rounded mb-3 mw-100" alt="Gambar soal">
                    @endif
                    @if($bank->type==='mc')
                        <div class="d-flex flex-column gap-2">
                            @foreach($bank->options as $opt)
                                <div class="border rounded p-2 {{ $opt->is_correct?'border-success bg-light-success':'' }}">
                                    <span class="badge badge-{{ $opt->is_correct?'success':'secondary' }} me-2">{{ $opt->label }}</span>
                                    {{ $opt->option_text }}
                                    @if($opt->is_correct)<span class="badge badge-light-success ms-2">kunci jawaban</span>@endif
                                    @if($opt->image_path)
                                        <img src="{{ asset('storage/'.$opt->image_path) }}" class="rounded d-block mt-2 mh-80px" alt="Gambar opsi">
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-muted fs-8">Soal essay — tidak ada pilihan jawaban.</div>
                    @endif
                    <div class="separator my-4"></div>
                    <div class="text-muted fs-8">Dibuat oleh {{ $bank->creator->name ?? '-' }}
                        · {{ $bank->created_at?->format('d M Y H:i') }}</div>
                </div>
            </div></div>
        </div>