<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pemberitahuan Materi/Pengumuman LMS Sync</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f9; color: #333333; margin: 0; padding: 0; }
        .wrapper { width: 100%; background-color: #f4f6f9; padding: 20px 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); padding: 30px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 0.5px; }
        .content { padding: 30px; line-height: 1.6; }
        .greeting { font-size: 18px; font-weight: bold; color: #2d3748; margin-bottom: 15px; }
        .details-box { background-color: #f7fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .detail-row { display: flex; margin-bottom: 10px; border-bottom: 1px dashed #edf2f7; padding-bottom: 8px; }
        .detail-row:last-child { margin-bottom: 0; border-bottom: none; padding-bottom: 0; }
        .detail-label { width: 130px; font-weight: bold; color: #4a5568; }
        .detail-value { flex-grow: 1; color: #2d3748; }
        .btn { display: inline-block; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: #ffffff !important; padding: 12px 25px; border-radius: 30px; text-decoration: none; font-weight: bold; text-align: center; margin: 20px 0 10px 0; box-shadow: 0 4px 6px rgba(50,50,93,0.11), 0 1px 3px rgba(0,0,0,0.08); }
        .footer { background-color: #edf2f7; padding: 20px; text-align: center; font-size: 12px; color: #718096; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1>MATERI & PENGUMUMAN BARU</h1>
            </div>
            <div class="content">
                <p class="greeting">Halo Bapak/Ibu {{ $parentName }},</p>
                
                <p>Kami ingin menginformasikan bahwa guru telah mengunggah <strong>Materi Pembelajaran Baru</strong> untuk putra/putri Anda:</p>

                <div class="details-box">
                    <div class="detail-row">
                        <div class="detail-label">Nama Siswa</div>
                        <div class="detail-value">{{ $studentName }}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Mata Pelajaran</div>
                        <div class="detail-value">{{ $subjectName }}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Judul Materi</div>
                        <div class="detail-value"><strong>{{ $moduleTitle }}</strong></div>
                    </div>
                    @if($zoomLink)
                        <div class="detail-row">
                            <div class="detail-label">Kelas Virtual (Zoom)</div>
                            <div class="detail-value" style="color: #2b6cb0; font-weight: bold;">
                                Tersedia ✓
                            </div>
                        </div>
                    @endif
                    @if($description)
                        <div class="detail-row" style="display: block; margin-top: 15px;">
                            <div class="detail-label" style="margin-bottom: 5px;">Deskripsi/Pengumuman:</div>
                            <div class="detail-value" style="background: #ffffff; padding: 10px; border-radius: 4px; border: 1px solid #edf2f7; font-style: italic;">
                                {{ $description }}
                            </div>
                        </div>
                    @endif
                </div>

                <p>Materi pembelajaran ini sudah dapat diakses dan diunduh oleh putra/putri Anda melalui portal LMS Sync.</p>

                <div style="text-align: center;">
                    <a href="{{ $loginUrl }}" class="btn" target="_blank">Buka Portal LMS</a>
                </div>
            </div>
            <div class="footer">
                <p>Email ini dikirimkan secara otomatis oleh sistem LMS Sync.</p>
                <p>&copy; {{ date('Y') }} LMS Sync. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
