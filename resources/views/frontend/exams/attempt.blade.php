@extends('frontend.layout.app')
@section('title', 'Mengerjakan: ' . $exam->title)

@section('content')
<div class="app-content flex-column-fluid">
    <div class="app-container container-xxl py-6">

        {{-- Sticky bar: judul + timer + submit --}}
        <div class="card shadow-sm mb-6 position-sticky" style="top:84px;z-index:30">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3 py-4">
                <div>
                    <h3 class="fw-bold text-gray-900 mb-0">{{ $exam->title }}</h3>
                    <span class="text-muted fs-7">{{ $session->name }} • {{ $questions->count() }} soal <span id="saveStatus" class="ms-2 text-success"></span></span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="badge badge-light-danger fs-4 fw-bold py-3 px-4"><i class="ki-outline ki-timer fs-3 me-1"></i> <span id="timer">--:--</span></div>
                    <button type="button" id="btnSubmit" class="btn btn-primary"><i class="ki-outline ki-check fs-4"></i> Kumpulkan</button>
                </div>
            </div>
        </div>

        @php $no = 0; @endphp
        @foreach($questions as $q)
        @php
            $no++;
            $ans = $answers->get($q->id);
            $opts = $q->type === 'mc' ? ($session->shuffle_options ? $q->options->shuffle(crc32($attempt->id.$q->id)) : $q->options) : collect();
        @endphp
        <div class="card mb-4" id="qcard-{{ $q->id }}">
            <div class="card-body">
                <div class="d-flex mb-3">
                    <span class="badge badge-circle badge-primary fs-6 me-3">{{ $no }}</span>
                    <div class="fw-semibold text-gray-900 fs-5">{!! nl2br(e($q->question_text)) !!}</div>
                </div>
                @if($q->image_path)<img src="{{ Storage::url($q->image_path) }}" class="rounded mb-4 mh-200px d-block">@endif

                @if($q->type === 'mc')
                    <div class="d-flex flex-column gap-2">
                        @foreach($opts as $opt)
                        <label class="d-flex align-items-center border rounded p-3 cursor-pointer opt-label {{ $ans && $ans->selected_option_id === $opt->id ? 'border-primary bg-light-primary' : 'border-gray-300' }}">
                            <input class="form-check-input me-3 ans-mc" type="radio" name="q_{{ $q->id }}" value="{{ $opt->id }}"
                                data-question="{{ $q->id }}" {{ $ans && $ans->selected_option_id === $opt->id ? 'checked' : '' }}>
                            <span class="badge badge-light-primary me-2">{{ $opt->label }}</span>
                            <span class="text-gray-800">{{ $opt->option_text }}</span>
                        </label>
                        @endforeach
                    </div>
                @else
                    <textarea class="form-control ans-essay" rows="5" data-question="{{ $q->id }}" placeholder="Tulis jawaban Anda di sini...">{{ $ans->answer_text ?? '' }}</textarea>
                @endif
            </div>
        </div>
        @endforeach

        <div class="text-center py-4">
            <button type="button" id="btnSubmit2" class="btn btn-primary btn-lg px-10"><i class="ki-outline ki-check fs-3"></i> Kumpulkan Ujian</button>
        </div>
    </div>
</div>

{{-- form submit tersembunyi --}}
<form id="submitForm" action="{{ route('student.exams.submit', $session->id) }}" method="POST" class="d-none">@csrf</form>

@push('scripts')
<script>
    const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const SAVE_URL = "{{ route('student.exam-answers.save') }}";
    const ATTEMPT_ID = "{{ $attempt->id }}";
    let remaining = {{ (int) $remaining }};
    let submitting = false;

    // ---------- Timer ----------
    const timerEl = document.getElementById('timer');
    function fmt(s){ const m=Math.floor(s/60), x=s%60; return String(m).padStart(2,'0')+':'+String(x).padStart(2,'0'); }
    function tick(){
        if (remaining <= 0){ timerEl.textContent='00:00'; autoSubmit(); return; }
        timerEl.textContent = fmt(remaining);
        if (remaining <= 60) timerEl.parentElement.classList.add('bg-danger','text-white');
        remaining--;
    }
    tick(); const timerInt = setInterval(tick, 1000);

    // ---------- Autosave ----------
    const status = document.getElementById('saveStatus');
    function setStatus(t, ok=true){ status.textContent = t; status.className = 'ms-2 ' + (ok?'text-success':'text-danger'); }

    function save(payload){
        return fetch(SAVE_URL, {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify(Object.assign({attempt_id: ATTEMPT_ID}, payload))
        }).then(async res => {
            if (res.status === 422){ const d = await res.json(); if (d.expired){ clearInterval(timerInt); autoSubmit(); } setStatus('Gagal menyimpan', false); return; }
            setStatus('✓ Tersimpan');
        }).catch(() => setStatus('Gagal menyimpan', false));
    }

    // MC: simpan saat pilih
    document.querySelectorAll('.ans-mc').forEach(r => {
        r.addEventListener('change', function(){
            document.querySelectorAll('#qcard-'+this.dataset.question+' .opt-label').forEach(l => l.classList.remove('border-primary','bg-light-primary'));
            this.closest('.opt-label').classList.add('border-primary','bg-light-primary');
            setStatus('Menyimpan...');
            save({question_id: this.dataset.question, selected_option_id: this.value});
        });
    });

    // Essay: debounce
    let timers = {};
    document.querySelectorAll('.ans-essay').forEach(t => {
        t.addEventListener('input', function(){
            const qid = this.dataset.question, val = this.value;
            setStatus('Mengetik...');
            clearTimeout(timers[qid]);
            timers[qid] = setTimeout(() => { setStatus('Menyimpan...'); save({question_id: qid, answer_text: val}); }, 900);
        });
    });

    // ---------- Submit ----------
    function doSubmit(){ submitting = true; clearInterval(timerInt); document.getElementById('submitForm').submit(); }
    function autoSubmit(){ if (submitting) return; Swal.fire({title:'Waktu Habis!', text:'Jawaban dikumpulkan otomatis.', icon:'info', allowOutsideClick:false, timer:2500, timerProgressBar:true}).then(doSubmit); }
    function confirmSubmit(){
        Swal.fire({title:'Kumpulkan ujian?', text:'Jawaban tidak bisa diubah setelah dikumpulkan.', icon:'question', showCancelButton:true, confirmButtonText:'Ya, Kumpulkan', cancelButtonText:'Cek lagi', confirmButtonColor:'#4F46E5'})
          .then(r => { if (r.isConfirmed) doSubmit(); });
    }
    document.getElementById('btnSubmit').addEventListener('click', confirmSubmit);
    document.getElementById('btnSubmit2').addEventListener('click', confirmSubmit);

    // ---------- Peringatan keluar ----------
    window.addEventListener('beforeunload', function(e){ if (!submitting){ e.preventDefault(); e.returnValue=''; } });
</script>
@endpush
@endsection
