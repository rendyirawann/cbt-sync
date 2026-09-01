<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pemberitahuan Tugas CBT Sync</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f9; color: #333333; margin: 0; padding: 0; }
        .wrapper { width: 100%; background-color: #f4f6f9; padding: 20px 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); padding: 30px; text-align: center; color: #ffffff; }
        .header h1 { margin: 0; font-size: 24px; font-weight: bold; letter-spacing: 0.5px; }
        .content { padding: 30px; line-height: 1.6; }
        .greeting { font-size: 18px; font-weight: bold; color: #2d3748; margin-bottom: 15px; }
        .alert-box { background-color: #fffaf0; border-left: 4px solid #dd6b20; padding: 15px; border-radius: 6px; margin: 20px 0; }
        .details-box { background-color: #f7fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .detail-row { display: flex; margin-bottom: 10px; border-bottom: 1px dashed #edf2f7; padding-bottom: 8px; }
        .detail-row:last-child { margin-bottom: 0; border-bottom: none; padding-bottom: 0; }
        .detail-label { width: 130px; font-weight: bold; color: #4a5568; }
        .detail-value { flex-grow: 1; color: #2d3748; }
        .btn { display: inline-block; background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); color: #ffffff !important; padding: 12px 25px; border-radius: 30px; text-decoration: none; font-weight: bold; text-align: center; margin: 20px 0 10px 0; box-shadow: 0 4px 6px rgba(50,50,93,0.11), 0 1px 3px rgba(0,0,0,0.08); }
        .footer { background-color: #edf2f7; padding: 20px; text-align: center; font-size: 12px; color: #718096; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .badge-warning { background-color: #feebc8; color: #c05621; }
        .badge-info { background-color: #bee3f8; color: #2b6cb0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1>CBT SYNC NOTIFICATION</h1>
            </div>
            <div class="content">
                <p class="greeting">Halo Bapak/Ibu {{ $parentName }},</p>
                
                @if($isReminder)
                    <div class="alert-box">
                        <strong>⚠️ PENGINGAT DEADLINE TUGAS:</strong> Putra/putri Anda memiliki tugas yang mendekati batas waktu pengumpulan dan belum dikirimkan.
                    </div>
                @else
                    <p>Kami ingin menginformasikan bahwa ada <strong>Tugas Baru</strong> yang telah diterbitkan untuk putra/putri Anda:</p>
                @endif

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
                        <div class="detail-label">Judul Tugas</div>
                        <div class="detail-value"><strong>{{ $assignmentTitle }}</strong></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Batas Waktu</div>
                        <div class="detail-value" style="color: #e53e3e; font-weight: bold;">
                            {{ \Carbon\Carbon::parse($dueDate)->format('d M Y, H:i') }} WIB
                        </div>
                    </div>
                    @if($description)
                        <div class="detail-row" style="display: block; margin-top: 15px;">
                            <div class="detail-label" style="margin-bottom: 5px;">Deskripsi Tugas:</div>
                            <div class="detail-value" style="background: #ffffff; padding: 10px; border-radius: 4px; border: 1px solid #edf2f7; font-style: italic;">
                                {{ $description }}
                            </div>
                        </div>
                    @endif
                </div>

                <p>Mohon dampingi dan ingatkan putra/putri Anda untuk menyelesaikan tugas tersebut sebelum batas waktu yang ditentukan.</p>

                <div style="text-align: center;">
                    <a href="{{ $loginUrl }}" class="btn" target="_blank">Buka Portal LMS</a>
                </div>
            </div>
            <div class="footer">
                <p>Email ini dikirimkan secara otomatis oleh sistem CBT Sync.</p>
                <p>&copy; {{ date('Y') }} CBT Sync. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
