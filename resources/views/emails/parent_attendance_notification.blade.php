<!DOCTYPE html>
<html>
<head>
    <style>
        .container { font-family: sans-serif; padding: 20px; line-height: 1.6; }
        .details { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
        .status-hadir { color: green; font-weight: bold; }
        .status-terlambat { color: orange; font-weight: bold; }
        .status-alpa { color: red; font-weight: bold; }
        .footer { margin-top: 30px; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Laporan Absensi Siswa</h2>
        <p>Halo Bapak/Ibu Orang Tua/Wali,</p>
        <p>Berikut adalah laporan absensi terbaru untuk putra/putri Anda:</p>
        
        <div class="details">
            <p><strong>Nama Siswa:</strong> {{ $studentName }}</p>
            <p><strong>Tipe Absen:</strong> {{ ucfirst($attendance->type == 'mapel' ? 'Mata Pelajaran' : $attendance->type) }}</p>
            @if($attendance->type == 'mapel' && $attendance->schedule)
                <p><strong>Mata Pelajaran:</strong> {{ $attendance->schedule->teachingAssignment->subject->name }}</p>
            @endif
            <p><strong>Waktu:</strong> {{ \Carbon\Carbon::parse($attendance->attended_at)->format('d M Y H:i') }}</p>
            <p><strong>Status:</strong> <span class="status-{{ $attendance->status }}">{{ ucfirst($attendance->status) }}</span></p>
            @if($attendance->late_minutes > 0)
                <p><strong>Keterangan:</strong> Terlambat {{ $attendance->late_minutes }} menit</p>
            @endif
        </div>

        <p>Laporan ini dikirim secara otomatis oleh sistem CBT Sync sebagai bentuk transparansi kehadiran siswa.</p>
        
        <div class="footer">
            &copy; {{ date('Y') }} CBT Sync. All rights reserved.
        </div>
    </div>
</body>
</html>
