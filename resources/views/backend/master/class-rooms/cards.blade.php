{{--
    Lembar kartu login peserta untuk dicetak (PDF).
    Dua kartu per baris, empat baris per halaman A4 potret.
    Format kartunya sendiri ada di partial _kartu-isi supaya sama persis
    dengan kartu yang dilihat siswa di My Profile.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
@include('backend.master.class-rooms._kartu-gaya')
<style>
    @page { margin: 10mm 8mm; }
    body { margin: 0; }
    table.lembar { width: 100%; border-collapse: separate; border-spacing: 4mm 4mm; }
    table.lembar > tr > td { width: 50%; vertical-align: top; }
</style>
</head>
<body class="kartu-wrap">
@php
    // Judul baris kedua: nama sekolah, seperti nama penyelenggara pada kartu ANBK.
    $penyelenggara = strtoupper($classRoom->school->name ?? 'SEKOLAH');
    $labelTahun = $tahun->name ?? '-';
@endphp

@foreach($peserta->chunk(2) as $pasangan)
    {{-- Satu <table class="lembar"> per pasangan: Dompdf boleh memutus halaman
         di antara tabel, tapi tidak akan memotong satu kartu di tengah. --}}
    <table class="lembar">
        <tr>
            @foreach($pasangan as $s)
                <td>
                    @include('backend.master.class-rooms._kartu-isi', ['sandi' => $sandi[$s->id] ?? null])
                </td>
            @endforeach
            @if($pasangan->count() === 1)
                <td></td>  {{-- penyeimbang kolom pada baris terakhir yang ganjil --}}
            @endif
        </tr>
    </table>
@endforeach
</body>
</html>
