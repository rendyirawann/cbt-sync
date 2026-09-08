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
                        <button type="button" class="btn btn-sm btn-light-secondary btn-lihat-bank"
                            data-id="{{ $bank->id }}"><i class="ki-outline ki-eye fs-5 me-1"></i>Lihat</button>
                        {{-- Ubah & Hapus dibuang: sumber kebenaran soal adalah soal
                             ujiannya, bukan cerminan di bank ini. Menyunting di sini
                             tidak mengubah ujian mana pun dan akan tertimpa sendiri
                             saat soal ujian disimpan lagi; menghapus bisa mencabut
                             soal yang sekolah lain sudah pakai. Penghapusan tetap
                             ada di jalur hapus ujian, yang punya konfirmasi dan
                             hitungan dampak lintas sekolah. --}}
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
