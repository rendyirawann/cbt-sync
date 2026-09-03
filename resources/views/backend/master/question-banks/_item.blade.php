{{-- Satu kartu soal Bank: kartu ringkas + modal pratinjau.
     Dipakai berulang di dalam panel kelompok per ujian (index.blade.php).
     Butuh variabel: $bank, $nomor, $isKepsek. --}}
        <div class="card mb-3"><div class="card-body d-flex py-4">
            <div class="me-4"><span class="badge badge-circle badge-light-primary fs-7">{{ $nomor }}</span></div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between flex-wrap gap-2 mb-1">
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge badge-light-{{ $bank->type==='mc'?'primary':'info' }}">{{ $bank->type==='mc'?'Pilihan Ganda':'Essay' }}</span>
                        <span class="badge badge-light-success">{{ $bank->subject->name ?? '-' }}</span>
                        <span class="badge badge-light-dark">{{ $bank->school->name ?? 'Tanpa sekolah' }}</span>
                        @if($bank->sourceSchool)
                            <span class="badge badge-light text-muted">sumber: {{ $bank->sourceSchool->name }}</span>
                        @endif
                        @if($bank->level)<span class="badge badge-light-warning">Tingkat {{ $bank->level }}</span>@endif
                        <span class="badge badge-light">{{ rtrim(rtrim((string)$bank->points,'0'),'.') }} poin</span>
                    </div>
                    <div class="d-flex gap-1">
                        {{-- Pratinjau: baca saja, jadi tersedia untuk semua peran. --}}
                        <button class="btn btn-sm btn-light-secondary" data-bs-toggle="modal"
                            data-bs-target="#viewBank{{ $bank->id }}"><i class="ki-outline ki-eye fs-5 me-1"></i>Lihat</button>
                    @unless($isKepsek)
                        <button class="btn btn-sm btn-icon btn-light-primary" data-bs-toggle="modal" data-bs-target="#editBank{{ $bank->id }}"><i class="ki-outline ki-pencil fs-5"></i></button>
                        <form action="{{ route('question-banks.destroy', $bank->id) }}" method="POST" class="d-inline custom-ajax-confirm">@csrf @method('DELETE')
                            <button class="btn btn-sm btn-icon btn-light-danger btn-delete"><i class="ki-outline ki-trash fs-5"></i></button>
                        </form>
                    @endunless
                    </div>
                </div>
                <div class="fw-semibold text-gray-900 mb-1">{!! nl2br(e($bank->question_text)) !!}</div>
                @if($bank->image_path)<img src="{{ asset('storage/'.$bank->image_path) }}" class="rounded mb-2 mh-90px">@endif
                @if($bank->type==='mc')
                <div class="d-flex flex-column gap-1 fs-8">
                    @foreach($bank->options as $opt)
                    <div class="{{ $opt->is_correct?'text-success fw-bold':'text-gray-600' }}"><span class="badge badge-{{ $opt->is_correct?'success':'secondary' }} me-1">{{ $opt->label }}</span> {{ $opt->option_text }}@if($opt->image_path) <i class="ki-outline ki-picture fs-7"></i>@endif</div>
                    @endforeach
                </div>
                @endif
            </div>
        </div></div>

        {{-- Pratinjau soal bank (baca saja) — dipakai tombol "Lihat". --}}
        <div class="modal fade" id="viewBank{{ $bank->id }}" tabindex="-1" aria-hidden="true">
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
