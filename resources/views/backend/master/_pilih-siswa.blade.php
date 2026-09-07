{{--
    Pemilih siswa secara massal (dipakai bersama oleh sesi ujian & plotting rombel).

    Menggantikan <select multiple>/select2 yang menuntut tahan Ctrl/Cmd — pada
    daftar puluhan siswa cara itu mudah membuat pilihan hilang gara-gara satu
    klik tanpa Ctrl. Di sini tiap siswa punya kotak centang sendiri, plus:
      • pencarian (nama / NISN / email),
      • Pilih semua & Kosongkan yang mengikuti hasil pencarian,
      • klik + Shift untuk mencentang satu rentang sekaligus,
      • penghitung jumlah terpilih.

    Parameter:
      $students  — koleksi Student (relasi user sudah dimuat)
      $terpilih  — array id siswa yang sudah tercentang (opsional)
      $uid       — id unik blok ini karena satu halaman memuat banyak modal
      $name      — nama field yang dikirim (bawaan: students[])
      $kosong    — teks bila daftarnya memang kosong (opsional)
--}}
@php
    $terpilih = $terpilih ?? [];
    $uid = $uid ?? 'ps' . uniqid();
    $name = $name ?? 'students[]';
    $kosong = $kosong ?? 'Belum ada siswa pada daftar ini.';
@endphp
<div class="js-pilih-siswa" id="{{ $uid }}">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
        <div class="position-relative flex-grow-1" style="min-width:180px">
            <i class="ki-outline ki-magnifier fs-5 position-absolute top-50 translate-middle-y ms-3 text-muted"></i>
            <input type="text" class="form-control form-control-sm ps-10 js-cari" placeholder="Cari nama, NISN, atau email…" autocomplete="off">
        </div>
        <button type="button" class="btn btn-sm btn-light-primary js-semua">Pilih semua</button>
        <button type="button" class="btn btn-sm btn-light js-kosong">Kosongkan</button>
        <span class="badge badge-light js-jumlah">0 dipilih</span>
    </div>

    <div class="border rounded" style="max-height:34vh;overflow:auto">
        @forelse($students as $st)
            <label class="js-baris d-flex align-items-center gap-3 px-3 py-2 border-bottom cursor-pointer"
                data-cari="{{ \Illuminate\Support\Str::lower(($st->user->name ?? '') . ' ' . ($st->nisn ?? '') . ' ' . ($st->user->email ?? '')) }}">
                <input class="form-check-input js-item" type="checkbox" name="{{ $name }}" value="{{ $st->id }}" @checked(in_array($st->id, $terpilih))>
                <span>
                    <span class="d-block fw-semibold text-gray-900 fs-7">{{ $st->user->name ?? 'Siswa' }}</span>
                    <span class="d-block text-muted fs-8">{{ $st->nisn ?: 'tanpa NISN' }} · {{ $st->user->email ?? '-' }}</span>
                </span>
            </label>
        @empty
            <div class="text-muted fs-8 px-3 py-4">{{ $kosong }}</div>
        @endforelse
        <div class="text-muted fs-8 px-3 py-4 js-kosong-hasil" style="display:none">Tidak ada siswa yang cocok dengan pencarian.</div>
    </div>
</div>

@once
@push('scripts')
<script>
    // Satu handler untuk SEMUA blok .js-pilih-siswa di halaman ini. Dibungkus
    // directive once di Blade, jadi skrip ini hanya dicetak sekali walau
    // partialnya di-include berkali-kali.
    // "Pilih semua"/"Kosongkan" hanya menyentuh baris yang sedang tampil supaya
    // bisa dipakai bersama pencarian (mis. cari "MM-X" lalu pilih semua hasilnya).
    document.querySelectorAll('.js-pilih-siswa').forEach(function (blok) {
        var cari     = blok.querySelector('.js-cari');
        var jumlah   = blok.querySelector('.js-jumlah');
        var hampa    = blok.querySelector('.js-kosong-hasil');
        var baris    = Array.prototype.slice.call(blok.querySelectorAll('.js-baris'));
        var terakhir = null;   // untuk pilih rentang dengan Shift

        function tampil() { return baris.filter(function (b) { return b.style.display !== 'none'; }); }
        function kotak(b) { return b.querySelector('.js-item'); }

        function hitung() {
            var n = blok.querySelectorAll('.js-item:checked').length;
            jumlah.textContent = n + ' dipilih';
            jumlah.className = 'badge js-jumlah ' + (n ? 'badge-light-primary' : 'badge-light');
        }

        if (cari) cari.addEventListener('input', function () {
            var kunci = this.value.trim().toLowerCase();
            baris.forEach(function (b) {
                b.style.display = (!kunci || b.dataset.cari.indexOf(kunci) !== -1) ? '' : 'none';
            });
            if (hampa) hampa.style.display = (baris.length && tampil().length === 0) ? '' : 'none';
        });

        blok.querySelector('.js-semua').addEventListener('click', function () {
            tampil().forEach(function (b) { kotak(b).checked = true; });
            hitung();
        });
        blok.querySelector('.js-kosong').addEventListener('click', function () {
            tampil().forEach(function (b) { kotak(b).checked = false; });
            hitung();
        });

        blok.addEventListener('click', function (e) {
            var item = e.target.closest('.js-item');
            if (!item) return;
            var b = item.closest('.js-baris');
            if (e.shiftKey && terakhir && terakhir !== b) {
                var t = tampil(), a = t.indexOf(terakhir), z = t.indexOf(b);
                if (a > -1 && z > -1) {
                    t.slice(Math.min(a, z), Math.max(a, z) + 1)
                     .forEach(function (r) { kotak(r).checked = item.checked; });
                }
            }
            terakhir = b;
            hitung();
        });

        hitung();
    });
</script>
@endpush
@endonce
