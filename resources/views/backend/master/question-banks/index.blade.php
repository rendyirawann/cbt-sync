@extends('backend.layout.app')
@section('title', 'Bank Soal Bersama')

@section('content')
@include('partials.katex')
@include('partials.math-editor')
@php $isKepsek = auth()->user()->hasRole('Kepala Sekolah'); @endphp

<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0">Bank Soal Bersama</h1>
            <span class="text-muted fs-7 pt-1">Soal reusable lintas sekolah — bisa ditarik ke ujian mana pun.</span>
        </div>
        @unless($isKepsek)
        @endunless
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        {{-- Filter --}}
        <div class="card mb-5"><div class="card-body py-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2"><label class="form-label fs-8">Sekolah</label>
                    <select name="school_id" class="form-select form-select-sm">
                        <option value="">Semua sekolah</option>
                        @foreach($schools as $sc)<option value="{{ $sc->id }}" @selected(request('school_id')===$sc->id)>{{ $sc->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label fs-8">Mata Pelajaran</label>
                    <select name="subject_id" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach($subjects as $s)<option value="{{ $s->id }}" @selected(request('subject_id')===$s->id)>{{ $s->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label fs-8">Tingkat</label>
                    <select name="level" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach($levels as $lv)<option value="{{ $lv }}" @selected(request('level')===$lv)>{{ $lv }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label fs-8">Tipe</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="mc" @selected(request('type')==='mc')>Pilihan Ganda</option>
                        <option value="essay" @selected(request('type')==='essay')>Essay</option>
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label fs-8">Cari pertanyaan</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="kata kunci...">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-sm btn-primary flex-grow-1"><i class="ki-outline ki-magnifier fs-5"></i> Filter</button>
                    <a href="{{ route('question-banks.index') }}" class="btn btn-sm btn-light">Reset</a>
                </div>
            </form>
        </div></div>

        {{-- Daftar --}}
        @forelse($banks as $i => $bank)
        <div class="card mb-3"><div class="card-body d-flex py-4">
            <div class="me-4"><span class="badge badge-circle badge-light-primary fs-7">{{ $banks->firstItem() + $i }}</span></div>
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

        @unless($isKepsek)
        @include('backend.master.question-banks._form', ['mode'=>'edit','bank'=>$bank])
        @endunless
        @empty
        <div class="card"><div class="card-body text-center py-10 text-muted">Belum ada soal di Bank. Soal masuk ke sini <b>otomatis</b> setiap kali guru menambah soal saat menyusun ujian.</div></div>
        @endforelse

        <div class="mt-4">{{ $banks->links() }}</div>
    </div>
</div>

@unless($isKepsek)
@endunless

@push('scripts')
<script>
    // Toggle bagian opsi berdasarkan Tipe (PG/Essay) di tiap form bank
    function toggleBankOptions(form){
        var type = form.querySelector('select[name=type]');
        var wrap = form.querySelector('.mc-section');
        if (!type || !wrap) return;
        wrap.style.display = type.value === 'mc' ? 'block' : 'none';
    }
    document.querySelectorAll('form.bank-form').forEach(f => { toggleBankOptions(f); f.querySelector('select[name=type]')?.addEventListener('change', ()=>toggleBankOptions(f)); });

    // MC option rows (renumber/add/remove)
    function bankRenumber(c){ c.querySelectorAll('.mc-row').forEach((row,idx)=>{ var r=row.querySelector('input[type=radio]'); if(r) r.value=idx; }); }
    document.querySelectorAll('.mc-options').forEach(c=>bankRenumber(c));
    document.addEventListener('click', function(e){
        if (e.target.closest('.mc-add')){
            const wrap = e.target.closest('.modal-body').querySelector('.mc-options');
            const row = document.createElement('div');
            row.className='mc-row border border-gray-300 rounded p-3 mb-2';
            row.innerHTML='<div class="d-flex align-items-start gap-3"><span class="pt-2"><input class="form-check-input mt-0" type="radio" name="correct"></span>'+
                '<div class="flex-grow-1"><input type="hidden" name="option_ids[]" value="">'+
                '<input type="text" name="options[]" class="form-control math-input mb-2" placeholder="Teks opsi (boleh $rumus$, boleh gambar)">'+
                '<input type="file" name="option_images[]" class="form-control form-control-sm" accept="image/*"></div>'+
                '<button type="button" class="btn btn-icon btn-light-danger mc-remove"><i class="ki-outline ki-trash fs-6"></i></button></div>';
            wrap.appendChild(row); bankRenumber(wrap);
        }
        if (e.target.closest('.mc-remove')){
            const wrap = e.target.closest('.mc-options'); const rows = wrap.querySelectorAll('.mc-row');
            if (rows.length>2){ e.target.closest('.mc-row').remove(); bankRenumber(wrap); } else { Swal.fire('Info','Minimal 2 opsi.','info'); }
        }
    });
    document.querySelectorAll('.custom-ajax-confirm .btn-delete').forEach(btn=>{
        btn.addEventListener('click', function(e){ e.preventDefault(); const form=this.closest('form');
            Swal.fire({title:'Yakin hapus soal bank ini?',icon:'warning',showCancelButton:true,confirmButtonText:'Ya',cancelButtonText:'Batal',confirmButtonColor:'#d33'}).then(r=>{ if(r.isConfirmed) form.submit(); });
        });
    });
</script>
@endpush
@endsection
