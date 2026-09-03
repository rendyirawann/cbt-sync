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
        {{-- Daftar dikelompokkan per UJIAN asal: satu kartu = satu ujian, isinya
             dibuka lewat tombol "Lihat" (panel lipat, bukan modal, supaya tombol
             Ubah/Hapus tiap soal tetap bisa membuka modalnya sendiri). --}}
        @forelse($groups as $g)
        @php
            $kunci = $g->source_exam_id ?? 'tanpa';
            $isi   = $items[$kunci] ?? collect();
            $satu  = $isi->first();
        @endphp
        <div class="card mb-3"><div class="card-body py-4">
            <div class="d-flex flex-stack flex-wrap gap-3">
                <div>
                    <div class="fw-bold fs-5 text-gray-900">{{ $g->source_exam_title ?? 'Tanpa ujian asal' }}</div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="badge badge-light-dark">{{ $satu?->school?->name ?? 'Tanpa sekolah' }}</span>
                        <span class="badge badge-light-success">{{ $satu?->subject?->name ?? '-' }}</span>
                        @if($satu?->level)<span class="badge badge-light-warning">Tingkat {{ $satu->level }}</span>@endif
                        <span class="badge badge-light-primary">{{ $g->jumlah }} soal</span>
                        @unless($g->source_exam_id)
                            <span class="badge badge-light text-muted">ujian asalnya sudah dihapus</span>
                        @endunless
                    </div>
                </div>
                <button class="btn btn-sm btn-light-primary" type="button" data-bs-toggle="collapse"
                    data-bs-target="#grupBank{{ $loop->index }}" aria-expanded="false">
                    <i class="ki-outline ki-eye fs-5 me-1"></i>Lihat {{ $g->jumlah }} soal
                </button>
            </div>
            <div class="collapse mt-4" id="grupBank{{ $loop->index }}">
                @foreach($isi as $urut => $bank)
                    @include('backend.master.question-banks._item', ['bank' => $bank, 'nomor' => $urut + 1])
                @endforeach
            </div>
        </div></div>

        {{-- Modal ubah diletakkan di luar panel lipat agar tidak bertumpuk. --}}
        @unless($isKepsek)
            @foreach($isi as $bank)
                @include('backend.master.question-banks._form', ['mode'=>'edit','bank'=>$bank])
            @endforeach
        @endunless
        @empty
        <div class="card"><div class="card-body text-center py-10 text-muted">Belum ada soal di Bank. Soal masuk ke sini <b>otomatis</b> setiap kali guru menambah soal saat menyusun ujian.</div></div>
        @endforelse

        <div class="mt-4">{{ $groups->links() }}</div>
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
