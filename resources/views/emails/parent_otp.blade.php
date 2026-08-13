<!DOCTYPE html>
<html>
<head>
    <style>
        .container { font-family: sans-serif; padding: 20px; line-height: 1.6; }
        .otp { font-size: 24px; font-weight: bold; color: #007bff; letter-spacing: 5px; margin: 20px 0; }
        .footer { margin-top: 30px; font-size: 12px; color: #777; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verifikasi Kontak Orang Tua</h2>
        <p>Halo,</p>
        <p>Kami menerima permintaan untuk memverifikasi kontak orang tua/wali dari siswa bernama <strong>{{ $studentName }}</strong> pada sistem LMS kami.</p>
        <p>Silakan gunakan kode verifikasi di bawah ini untuk melanjutkan:</p>
        <div class="otp">{{ $otp }}</div>
        <p>Kode ini berlaku selama 10 menit. Jika Anda tidak merasa melakukan permintaan ini, silakan abaikan email ini.</p>
        <div class="footer">
            &copy; {{ date('Y') }} CBT Sync. All rights reserved.
        </div>
    </div>
</body>
</html>
