{{--
    Pemilih peserta sesi secara massal.

    Menggantikan <select multiple> yang menuntut tahan Ctrl/Cmd — pada daftar
    puluhan siswa cara itu mudah membuat pilihan hilang gara-gara satu klik
    tanpa Ctrl. Di sini tiap siswa punya kotak centang sendiri, plus:
      • pencarian (nama / NISN / email),
      • Pilih semua & Kosongkan yang mengikuti hasil pencarian,
      • klik + Shift untuk mencentang satu rentang sekaligus,
      • penghitung jumlah terpilih.

    Parameter:
      $students  — koleksi Student (relasi user sudah dimuat)
      $terpilih  — array id siswa yang sudah tercentang (opsional)
      $uid       — id unik blok ini karena satu halaman memuat banyak modal
--}}
@php
    $terpilih = $terpilih ?? [];
    $uid = $uid ?? 'ps' . uniqid();
@endphp
<div class="js-pilih-siswa" id="{{ $uid }}">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
        <div class="position-relative flex-grow-1" style="min-width:180px">
            <i class="ki-outline ki-magnifier fs-5 position-absolute top-50 translate-middle-y ms-3 text-muted"></i>
            <input type="text" class="form-control form-control-sm ps-10 js-cari" placeholder="Cari nama, NISN, atau email…" autocomplete="off">
        </div>
        <button type="button" class="btn btn-sm btn-light-primary js-semua">Pilih semua</button>
        <button type="button" class="btn btn-sm btn-light js-kosong">Kosongkan</button>
        <span class="badge badge-light-primary js-jumlah">0 dipilih</span>
    </div>

    <div class="border rounded" style="max-height:34vh;overflow:auto">
        @forelse($students as $st)
            <label class="js-baris d-flex align-items-center gap-3 px-3 py-2 border-bottom cursor-pointer"
                data-cari="{{ \Illuminate\Support\Str::lower(($st->user->name ?? '') . ' ' . ($st->nisn ?? '') . ' ' . ($st->user->email ?? '')) }}">
                <input class="form-check-input js-item" type="checkbox" name="students[]" value="{{ $st->id }}" @checked(in_array($st->id, $terpilih))>
                <span>
                    <span class="d-block fw-semibold text-gray-900 fs-7">{{ $st->user->name ?? 'Siswa' }}</span>
                    <span class="d-block text-muted fs-8">{{ $st->nisn ?: 'tanpa NISN' }} · {{ $st->user->email ?? '-' }}</span>
                </span>
            </label>
        @empty
            <div class="text-muted fs-8 px-3 py-4">Belum ada siswa di kelas ini pada tahun ajaran ujian.</div>
        @endforelse
        <div class="text-muted fs-8 px-3 py-4 js-kosong-hasil" style="display:none">Tidak ada siswa yang cocok dengan pencarian.</div>
    </div>
</div>
