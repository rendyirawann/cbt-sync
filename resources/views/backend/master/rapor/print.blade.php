<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>e-Rapor_{{ str_replace(' ', '_', $student->user->name) }}_{{ $classRoom->name }}</title>
    <style>
        /* IMPORT GOOGLE FONTS FOR METRONIC-GRADE TYPOGRAPHY */
        @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700&family=Outfit:wght@300;400;600;700&family=Playfair+Display:ital,wght@0,600;1,400&display=swap');

        /* RESET & BASE PRINT LAYOUT */
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', 'Times New Roman', serif;
            color: #1e1e2d;
            background-color: #fff;
            margin: 0;
            padding: 0;
            font-size: 14px;
            line-height: 1.6;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* PAGE SIZE & DEFINITION */
        @page {
            size: A4;
            margin: 0; /* Handled by margins inside pages to allow full cover border */
        }

        /* DYNAMIC PAGE SEPARATOR (BOOK EFFECT) */
        .page {
            width: 210mm;
            height: 297mm;
            padding: 20mm;
            position: relative;
            background-color: #fff;
            page-break-after: always;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }

        /* COVER PAGE STYLING (SAMPLE BUKU RAPOR) */
        .cover-page {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            height: 100%;
            text-align: center;
            position: relative;
        }

        /* LUXURIOUS ROYAL BORDER FRAME FOR BOOK COVER */
        .cover-border {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border: 4px double #1B365D;
            padding: 15px;
            pointer-events: none;
        }

        .cover-inner-border {
            width: 100%;
            height: 100%;
            border: 1px solid #1B365D;
        }

        .cover-header {
            margin-top: 40px;
        }

        .cover-kementerian {
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #5a6a85;
            margin-bottom: 5px;
        }

        .cover-main-title {
            font-family: 'Cinzel', serif;
            font-size: 28px;
            font-weight: 700;
            color: #1B365D;
            letter-spacing: 3px;
            margin: 25px 0 5px 0;
            text-transform: uppercase;
        }

        .cover-sub-title {
            font-family: 'Playfair Display', serif;
            font-size: 18px;
            font-style: italic;
            color: #4a5a70;
            margin: 0;
        }

        /* EMBLEM / LOGO PLACEHOLDER */
        .cover-emblem-container {
            margin: 60px 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .cover-emblem {
            width: 130px;
            height: 130px;
            background: linear-gradient(135deg, #1B365D, #7239ea);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(27, 54, 93, 0.2);
            position: relative;
        }

        .cover-emblem i {
            color: white;
            font-size: 55px;
            font-weight: bold;
        }

        .cover-emblem::after {
            content: "LMS";
            position: absolute;
            bottom: -25px;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 4px;
            color: #1B365D;
        }

        /* STUDENT IDENTITY BOOKLET BOX */
        .cover-identity-box {
            width: 85%;
            border: 2px solid #1B365D;
            background-color: #fafbfd;
            padding: 25px 30px;
            border-radius: 6px;
            margin-bottom: 50px;
            box-shadow: inset 0 0 10px rgba(27, 54, 93, 0.05);
        }

        .cover-identity-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cover-identity-table td {
            padding: 8px 0;
            font-size: 15px;
            vertical-align: middle;
        }

        .cover-identity-table td.label {
            width: 38%;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 1px;
        }

        .cover-identity-table td.colon {
            width: 5%;
            color: #1B365D;
            font-weight: bold;
        }

        .cover-identity-table td.value {
            text-align: left;
            font-weight: 700;
            color: #1B365D;
            font-size: 16px;
        }

        .cover-footer {
            margin-bottom: 40px;
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            color: #718096;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .cover-footer strong {
            color: #1b365d;
        }

        /* PAGE 2: LEDGER CONTENTS */
        .ledger-container {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            width: 100%;
        }

        /* OFFICE KOP SURAT */
        .kop-surat {
            display: flex;
            align-items: center;
            border-bottom: 3px double #1e1e2d;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .kop-logo {
            width: 65px;
            height: 65px;
            background: #1B365D;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
        }

        .kop-logo span {
            color: white;
            font-weight: 800;
            font-size: 24px;
        }

        .kop-text-container {
            flex-grow: 1;
            text-align: left;
        }

        .school-title {
            font-family: 'Cinzel', serif;
            font-size: 22px;
            font-weight: 700;
            color: #1B365D;
            margin: 0 0 3px 0;
            text-transform: uppercase;
        }

        .school-details {
            font-size: 11px;
            margin: 0;
            color: #4a5568;
            font-style: italic;
        }

        .report-section-title {
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 25px;
            color: #1B365D;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
        }

        /* META TABLES IN LEDGER */
        .ledger-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .ledger-meta-table {
            width: 48%;
            border-collapse: collapse;
        }

        .ledger-meta-table td {
            padding: 4px 0;
        }

        .ledger-meta-table td.label {
            font-weight: 600;
            color: #4a5568;
            width: 38%;
        }

        .ledger-meta-table td.colon {
            width: 5%;
        }

        .ledger-meta-table td.value {
            font-weight: 700;
            color: #1e293b;
        }

        /* PREMIUM LEDGER TABLE */
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        .grades-table th, .grades-table td {
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
        }

        .grades-table th {
            background-color: #1B365D;
            color: white;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
        }

        .grades-table td {
            font-size: 13px;
        }

        .grades-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .font-bold {
            font-weight: 700 !important;
        }

        .text-center {
            text-align: center !important;
        }

        /* SCORE LETTER BADGES PRINT COMPATIBLE */
        .letter-grade-box {
            font-weight: 800;
            font-size: 14px;
            color: #1B365D;
        }

        /* CATATAN WALI KELAS BOX */
        .remarks-card {
            border: 1px solid #cbd5e1;
            background-color: #fafbfc;
            border-radius: 4px;
            padding: 12px 18px;
            margin-bottom: 25px;
        }

        .remarks-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1B365D;
            margin: 0 0 5px 0;
            border-bottom: 1px dashed #cbd5e1;
            padding-bottom: 3px;
        }

        .remarks-content {
            font-style: italic;
            font-size: 13px;
            color: #475569;
            margin: 0;
        }

        /* BOOKLET SUMMARY */
        .booklet-summary {
            display: flex;
            justify-content: space-between;
            border: 2px solid #1B365D;
            background-color: #f1f5f9;
            padding: 12px 25px;
            border-radius: 6px;
            margin-bottom: 35px;
        }

        .booklet-summary-item {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
        }

        .booklet-summary-item span {
            font-size: 16px;
            font-weight: 800;
            color: #1B365D;
            margin-left: 5px;
        }

        /* SIGNATURE BLOCKS */
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .sig-slot {
            width: 30%;
            text-align: center;
            font-size: 13px;
        }

        .sig-space {
            height: 65px;
        }

        .sig-name {
            font-weight: 700;
            text-decoration: underline;
            color: #1e293b;
        }

        /* INTERFACES FOR SCREEN INTERACTION */
        @media print {
            .no-print {
                display: none !important;
            }
            .page {
                border: none;
                box-shadow: none;
                margin: 0;
                padding: 20mm;
            }
        }

        .print-actions {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 10px 30px rgba(27, 54, 93, 0.15);
            border-radius: 8px;
            padding: 10px 15px;
            display: flex;
            gap: 10px;
            z-index: 9999;
            border: 1px solid #e2e8f0;
        }

        .btn-print {
            background-color: #10b981;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
            transition: all 0.2s ease;
        }

        .btn-print:hover {
            background-color: #059669;
            transform: translateY(-1px);
        }

        .btn-close {
            background-color: #6366f1;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 4px 6px rgba(99, 102, 241, 0.2);
            transition: all 0.2s ease;
        }

        .btn-close:hover {
            background-color: #4f46e5;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>

    {{-- FLOATING TOOLBAR --}}
    <div class="print-actions no-print">
        <button class="btn-print" onclick="window.print()">
            Cetak Rapor (Print / PDF)
        </button>
        <button class="btn-close" onclick="window.close()">
            Tutup Buku Rapor
        </button>
    </div>

    {{-- ==================== PAGE 1: COVER RAPOR (SAMPUL BUKU) ==================== --}}
    <div class="page">
        <div class="cover-border">
            <div class="cover-inner-border"></div>
        </div>
        
        <div class="cover-page">
            <div class="cover-header">
                <p class="cover-kementerian">Kementerian Pendidikan, Kebudayaan,<br>Riset, dan Teknologi Republik Indonesia</p>
                <h1 class="cover-main-title">Rapor Siswa</h1>
                <p class="cover-sub-title">Laporan Capaian Hasil Belajar Peserta Didik</p>
            </div>

            <div class="cover-emblem-container">
                <div class="cover-emblem">
                    <!-- Standard vector graphics insignia representing academic achievement -->
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 17L12 22L22 17" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 12L12 17L22 12" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>

            <div class="cover-identity-box">
                <table class="cover-identity-table">
                    <tr>
                        <td class="label">Nama Peserta Didik</td>
                        <td class="colon">:</td>
                        <td class="value">{{ $student->user->name }}</td>
                    </tr>
                    <tr>
                        <td class="label">NISN / NIS</td>
                        <td class="colon">:</td>
                        <td class="value">{{ $student->nisn }} / {{ $student->nis }}</td>
                    </tr>
                    <tr>
                        <td class="label">Rombongan Belajar</td>
                        <td class="colon">:</td>
                        <td class="value">{{ $classRoom->name }}</td>
                    </tr>
                    <tr>
                        <td class="label">Satuan Pendidikan</td>
                        <td class="colon">:</td>
                        <td class="value">{{ $student->school->name ?? 'LMS-Sync School' }}</td>
                    </tr>
                </table>
            </div>

            <div class="cover-footer">
                Tahun Ajaran <strong>{{ $academicYear->name }}</strong>
            </div>
        </div>
    </div>

    {{-- ==================== PAGE 2: LEDGER CONTENTS ==================== --}}
    <div class="page">
        <div class="ledger-container">
            <div>
                {{-- KOP SURAT --}}
                <div class="kop-surat">
                    <div class="kop-logo">
                        <span>L</span>
                    </div>
                    <div class="kop-text-container">
                        <h1 class="school-title">{{ $student->school->name ?? 'LMS-SYNC SCHOOL' }}</h1>
                        <p class="school-details">
                            Alamat: {{ $student->school->address ?? 'Jalan Raya Pendidikan No. 1' }} | Telp: {{ $student->school->phone ?? '-' }}
                        </p>
                    </div>
                </div>

                <h2 class="report-section-title">Laporan Hasil Pencapaian Kompetensi</h2>

                {{-- META DATA --}}
                <div class="ledger-meta">
                    <table class="ledger-meta-table">
                        <tr>
                            <td class="label">Nama Siswa</td>
                            <td class="colon">:</td>
                            <td class="value">{{ $student->user->name }}</td>
                        </tr>
                        <tr>
                            <td class="label">NISN / NIS</td>
                            <td class="colon">:</td>
                            <td class="value">{{ $student->nisn }} / {{ $student->nis }}</td>
                        </tr>
                    </table>
                    
                    <table class="ledger-meta-table">
                        <tr>
                            <td class="label">Kelas / Rombel</td>
                            <td class="colon">:</td>
                            <td class="value">{{ $classRoom->name }}</td>
                        </tr>
                        <tr>
                            <td class="label">Tahun Ajaran</td>
                            <td class="colon">:</td>
                            <td class="value">{{ $academicYear->name }}</td>
                        </tr>
                    </table>
                </div>

                {{-- GRADES LEDGER --}}
                <table class="grades-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;" class="text-center">No</th>
                            <th style="width: 40%;">Mata Pelajaran</th>
                            <th style="width: 25%;">Guru Pengampu</th>
                            <th style="width: 15%;" class="text-center">Tugas (Selesai/Total)</th>
                            <th style="width: 15%;" class="text-center">Nilai Angka</th>
                            <th style="width: 12%;" class="text-center">Predikat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 1; @endphp
                        @forelse($raporData['subjects'] as $sub)
                            <tr>
                                <td class="text-center">{{ $no++ }}</td>
                                <td class="font-bold">{{ $sub['subject_name'] }}</td>
                                <td>{{ $sub['teacher_name'] }}</td>
                                <td class="text-center">{{ $sub['completed_assignments'] }} / {{ $sub['total_assignments'] }}</td>
                                <td class="text-center font-bold">{{ $sub['average_score'] }}</td>
                                <td class="text-center"><span class="letter-grade-box">{{ $sub['letter_grade'] }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-5">Belum ada mata pelajaran terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- CATATAN WALI KELAS --}}
                <div class="remarks-card">
                    <h3 class="remarks-title">Catatan Wali Kelas</h3>
                    <p class="remarks-content">
                        @if($raporData['overall_average'] >= 86)
                            "Pertahankan prestasi luar biasa yang telah dicapai. Tetaplah rendah hati, rajin belajar, dan jadilah teladan yang menginspirasi teman-teman satu kelas!"
                        @elseif($raporData['overall_average'] >= 76)
                            "Hasil belajar yang sangat baik. Tingkatkan fokus pada mata pelajaran eksakta dan pertahankan semangat belajarnya untuk memperoleh hasil yang lebih maksimal!"
                        @elseif($raporData['overall_average'] >= 66)
                            "Perkembangan belajar yang cukup memuaskan. Kurangi waktu bermain, tingkatkan disiplin dalam menyelesaikan penugasan tepat waktu, dan perbanyak diskusi materi."
                        @else
                            "Butuh bimbingan dan pendampingan ekstra. Harap tingkatkan kerajinan dalam masuk kelas serta selalu berdiskusi dengan guru pengampu terkait penugasan."
                        @endif
                    </p>
                </div>
            </div>

            <div>
                {{-- SUMMARY BOX --}}
                <div class="booklet-summary">
                    <div class="booklet-summary-item">
                        RATA-RATA AKADEMIK: <span>{{ $raporData['overall_average'] }}</span>
                    </div>
                    <div class="booklet-summary-item">
                        HURUF MUTU: <span>{{ $raporData['overall_grade'] }}</span>
                    </div>
                    <div class="booklet-summary-item">
                        PERINGKAT KELAS: <span>{{ $raporData['rank'] }} dari {{ $raporData['total_students'] }} Siswa</span>
                    </div>
                </div>

                {{-- SIGNATURES --}}
                <div class="signatures">
                    <div class="sig-slot">
                        <p>Orang Tua / Wali Siswa</p>
                        <div class="sig-space"></div>
                        <p>_______________________</p>
                    </div>
                    
                    <div class="sig-slot">
                        <p>Wali Kelas</p>
                        <div class="sig-space"></div>
                        <p class="sig-name">
                            {{ count($raporData['subjects']) > 0 ? $raporData['subjects'][0]['teacher_name'] : 'Wali Kelas' }}
                        </p>
                    </div>

                    <div class="sig-slot">
                        <p>Mengetahui,<br>Kepala Sekolah</p>
                        <div class="sig-space"></div>
                        <p class="sig-name">Dr. H. Ahmad Fauzi, M.Pd.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto trigger browser print overlay
        window.addEventListener('DOMContentLoaded', (event) => {
            setTimeout(() => {
                window.print();
            }, 600);
        });
    </script>
</body>
</html>
