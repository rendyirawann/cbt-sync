{{-- Gaya kartu login peserta. Dipakai bersama oleh PDF (Dompdf) dan tampilan
     di My Profile, supaya format kartunya benar-benar satu sumber.
     Tata letaknya <table>, bukan flex/grid, karena Dompdf tidak mendukung keduanya. --}}
<style>
    .kartu-wrap { font-family: Helvetica, Arial, sans-serif; color: #000; }

    table.kartu { width: 100%; border: 1px solid #000; border-collapse: collapse; background: #fff; }
    table.kartu td { padding: 0; }

    /* --- kop: logo + judul ---
       Selektor disamakan kekhususannya dengan `table.kartu td` di atas; kalau
       hanya `td.kop`, aturan padding:0 itu yang menang dan teks menempel garis. --*/
    table.kartu td.kop { border-bottom: 1px solid #000; padding: 2mm 1.5mm; }
    table.kop-isi { width: 100%; border-collapse: collapse; }
    table.kop-isi td.logo { width: 16mm; text-align: center; vertical-align: middle; }
    table.kop-isi td.logo img { width: 13mm; }
    table.kop-isi td.judul { text-align: center; vertical-align: middle; line-height: 1.35; }
    .judul .j1 { font-size: 9.5pt; font-weight: bold; letter-spacing: .3pt; }
    .judul .j2 { font-size: 8.5pt; font-weight: bold; letter-spacing: .2pt; }
    .judul .j3 { font-size: 8.5pt; font-weight: bold; }

    /* --- isi: label : nilai --- */
    table.kartu td.isi { padding: 2.5mm 3.5mm 3mm 3.5mm; }
    table.baris { width: 100%; border-collapse: collapse; }
    table.baris td { font-size: 8pt; padding: 1.1mm 0; vertical-align: top; }
    table.baris td.l { width: 30mm; }
    table.baris td.s { width: 3mm; }
    table.baris td.tebal { font-weight: bold; font-size: 8.5pt; }
</style>
