{{--
    DAFTAR HADIR PESERTA — format mengikuti lembar daftar hadir ANBK.

    Satu lembar per GELOMBANG: kolom PUKUL diambil dari jam gelombang
    (Master Gelombang), karena jadwal ujian sendiri hanya berupa rentang tanggal.
    HARI dan TANGGAL sengaja dibiarkan kosong bergaris untuk ditulis tangan,
    sama seperti lembar aslinya — siswa boleh masuk kapan saja dalam rentang itu.

    Tata letak memakai <table> karena Dompdf tidak mendukung flex/grid.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 12mm 10mm; }
    body { font-family: Helvetica, Arial, sans-serif; color: #000; margin: 0; font-size: 9pt; }

    /* --- kop --- */
    table.kop { width: 100%; border-collapse: collapse; margin-bottom: 5mm; }
    table.kop td.logo { width: 20mm; vertical-align: top; }
    table.kop td.logo img { width: 17mm; }
    table.kop td.tajuk { text-align: center; vertical-align: middle; line-height: 1.45; }
    .tajuk .t1 { font-size: 12pt; font-weight: bold; }
    .tajuk .t2 { font-size: 11pt; font-weight: bold; }
    table.kop td.kanan { width: 20mm; }

    /* --- identitas: label : nilai bergaris --- */
    table.ident { width: 100%; border-collapse: collapse; margin-bottom: 4mm; }
    table.ident td { padding: 1.2mm 0; font-size: 9pt; vertical-align: bottom; }
    /* 42mm: label terpanjang sekarang "ID PROKTOR / RUANG". */
    td.il { width: 42mm; }
    td.is { width: 4mm; }
    td.iv { border-bottom: 1px solid #000; padding-left: 2mm !important; }
    td.ik { width: 18mm; text-align: right; padding-right: 2mm !important; }
    td.ikv { width: 30mm; border-bottom: 1px solid #000; padding-left: 2mm !important; }

    /* --- tabel peserta --- */
    table.peserta { width: 100%; border-collapse: collapse; }
    table.peserta th, table.peserta td { border: 1px solid #000; padding: 1.6mm 2mm; font-size: 8.5pt; }
    table.peserta th { text-align: center; font-weight: bold; }
    table.peserta td.no { width: 8mm; text-align: center; }
    table.peserta td.user { width: 34mm; }
    table.peserta td.ttd { width: 42mm; height: 7mm; }
    table.peserta td.mapel { width: 44mm; }
    /* Nomor tanda tangan digeser bergantian kiri/kanan supaya paraf tidak bertumpuk. */
    td.ttd.kanan { text-align: center; }

    /* --- catatan & tanda tangan --- */
    .ket { margin-top: 3mm; font-size: 8pt; }
    .ket b { font-size: 8.5pt; }
    .ket ol { margin: 1mm 0 0 4mm; padding: 0; }
    .ket li { margin-bottom: .6mm; }

    table.tutup { width: 100%; border-collapse: collapse; margin-top: 4mm; }
    table.tutup > tr > td { vertical-align: top; }
    table.rekap { border-collapse: collapse; font-size: 8pt; }
    table.rekap td { border: 1px solid #000; padding: 1.2mm 2mm; }
    table.rekap td.angka { width: 22mm; }

    /* Blok tanda tangan. Tanda kurung didorong ke tepi kiri & kanan kolom lewat
       sel tengah yang melebar, sehingga lebarnya sejajar dengan baris NIP di
       bawahnya — bukan mengandalkan deretan &nbsp; yang lebarnya menebak-nebak. */
    .ttdblok { font-size: 9pt; }
    .ttdblok .jabatan { text-align: center; }
    table.krg { width: 100%; border-collapse: collapse; margin-top: 16mm; }
    table.krg td { padding: 0; font-size: 9pt; }
    table.krg td.tepi { width: 3mm; }
    .ttdblok .nip { padding-top: .8mm; }
</style>
</head>
<body>
@php
    $sekolah = $exam->teachingAssignment->classRoom->school ?? null;
    $mapel = $exam->teachingAssignment->subject->name ?? '-';
    $garis = fn ($v) => filled($v) ? $v : '';
    // DUA kode di kop, keduanya berlabel "KODE" seperti lembar ANBK aslinya:
    // sebaris KOTA/KABUPATEN memakai schools.city_code, dan sebaris
    // SEKOLAH/MADRASAH memakai schools.school_code. Keduanya diisi di
    // Data Master -> Sekolah, bukan nilai tetap di berkas ini.

    // ID Proktor & Ruang adalah data PER SISWA (Data Master -> Data Siswa).
    // Satu lembar berlaku untuk satu ruang, jadi nilainya hanya dicetak bila
    // SELURUH peserta di gelombang ini memakai nilai yang sama; kalau berbeda
    // dibiarkan kosong agar lembarnya tidak menyesatkan.
    $satuNilai = fn ($kolom) => ($n = $peserta->pluck($kolom)->filter()->unique())->count() === 1
        ? $n->first() : '';
    $idProktor = $satuNilai('proctor_id');
    $ruangUjian = $satuNilai('room');
    // Ditulis sebaris dengan pemisah "/" seperti pada lembar aslinya.
    $proktorRuang = ($idProktor || $ruangUjian)
        ? ($idProktor ?: '-') . ' / ' . ($ruangUjian ?: '-')
        : '';
@endphp

<table class="kop">
    <tr>
        <td class="logo"><img src="{{ $logo }}" alt=""></td>
        <td class="tajuk">
            <div class="t1">DAFTAR HADIR PESERTA</div>
            <div class="t2">{{ strtoupper($exam->title) }}</div>
            <div class="t2">TAHUN {{ $tahun->name ?? '-' }}</div>
        </td>
        <td class="kanan"></td>
    </tr>
</table>

{{-- Baris HARI & TANGGAL memang DIBIARKAN KOSONG bergaris, sama seperti lembar
     aslinya: satu jadwal berlaku beberapa hari dan siswa boleh masuk kapan saja
     di dalamnya, jadi hari pelaksanaan tiap lembar ditulis tangan oleh pengawas
     saat ruangan dipakai. Rentang tanggalnya dicetak sebagai catatan di bawah.

     Baris ini disatukan dengan kop (bukan tabel terpisah) supaya kolom kanan
     KODE/KODE/SESI/PUKUL berbaris rapi pada satu kisi kolom. Sel isi di kiri
     memakai colspan="4" karena baris HARI/TANGGAL memecah bagian kiri menjadi
     enam sel. --}}
<table class="ident">
    <tr>
        <td class="il">KOTA/KABUPATEN</td><td class="is">:</td>
        <td class="iv" colspan="4">{{ $garis($sekolah->city ?? null) }}</td>
        <td class="ik">KODE</td><td class="is">:</td>
        <td class="ikv">{{ $garis($sekolah->city_code ?? null) }}</td>
    </tr>
    <tr>
        <td class="il">SEKOLAH/MADRASAH</td><td class="is">:</td>
        <td class="iv" colspan="4">{{ $garis($sekolah->name ?? null) }}</td>
        <td class="ik">KODE</td><td class="is">:</td>
        <td class="ikv">{{ $garis($sekolah->school_code ?? null) }}</td>
    </tr>
    <tr>
        <td class="il">ID PROKTOR / RUANG</td><td class="is">:</td>
        <td class="iv" colspan="4">{{ $proktorRuang }}</td>
        <td class="ik">SESI</td><td class="is">:</td>
        <td class="ikv">{{ $gelombang->name ?? '-' }}</td>
    </tr>
    <tr>
        <td class="il">HARI</td><td class="is">:</td>
        <td class="iv" style="width:38mm"></td>
        <td class="ik" style="width:24mm">TANGGAL</td><td class="is">:</td>
        <td class="iv"></td>
        <td class="ik">PUKUL</td><td class="is">:</td>
        <td class="ikv">{{ $gelombang->rentang_jam ?? '' }}</td>
    </tr>
</table>

{{-- Rentang tanggal jadwal ditaruh sebagai catatan, bukan baris kop: lembar
     aslinya membiarkan HARI/TANGGAL kosong untuk ditulis tangan sesuai hari
     pelaksanaan, sementara jadwal di sistem berupa rentang beberapa hari. --}}
@if($rentangTanggal)
    <div style="font-size:8pt;margin:-2mm 0 3mm 0">Rentang jadwal ujian: <b>{{ $rentangTanggal }}</b>
        (siswa boleh masuk kapan saja dalam rentang ini)</div>
@endif

<table class="peserta">
    <thead>
        <tr>
            <th>No.</th><th>Username</th><th>Nama Peserta</th><th>Tanda Tangan</th><th>Mata Pelajaran</th>
        </tr>
    </thead>
    <tbody>
        @forelse($peserta as $i => $s)
            <tr>
                <td class="no">{{ $i + 1 }}</td>
                <td class="user">{{ $s->user->username ?? '-' }}</td>
                <td>{{ $s->user->name ?? '-' }}</td>
                {{-- ganjil rata kiri, genap rata tengah: mengikuti lembar asli --}}
                <td class="ttd {{ ($i + 1) % 2 === 0 ? 'kanan' : '' }}">{{ $i + 1 }}.</td>
                <td class="mapel">{{ $mapel }}</td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center;padding:6mm">Tidak ada peserta pada gelombang ini.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="ket">
    <b>Keterangan :</b>
    <ol>
        <li>Dibuat rangkap 3 (tiga), masing-masing untuk sekolah, kota/kab dan Provinsi.</li>
        <li>Pengawas ruang menyilang Nama Peserta yang tidak hadir.</li>
        <li>Daftar hadir untuk pusat di upload melalui web ANBK.</li>
    </ol>
</div>

<table class="tutup">
    <tr>
        <td style="width:44%">
            <table class="rekap">
                <tr><td>Jumlah Peserta yang Seharusnya Hadir</td><td>:</td><td class="angka">{{ $peserta->count() }} peserta</td></tr>
                <tr><td>Jumlah Peserta yang Tidak Hadir</td><td>:</td><td class="angka">&nbsp;</td></tr>
                <tr><td>Jumlah Peserta Hadir</td><td>:</td><td class="angka">&nbsp;</td></tr>
            </table>
        </td>
        @foreach(['Proktor', 'Pengawas'] as $jabatan)
        <td style="width:26%;padding:0 5mm" class="ttdblok">
            <div class="jabatan">{{ $jabatan }}</div>
            <table class="krg">
                <tr><td class="tepi">(</td><td></td><td class="tepi" style="text-align:right">)</td></tr>
            </table>
            <div class="nip">NIP.</div>
        </td>
        @endforeach
    </tr>
</table>
</body>
</html>
