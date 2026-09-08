{{-- Halaman pemilihan menu login.

     Dipasang di AKAR domain supaya pengguna tidak perlu mengganti-ganti URL:
     dari sini siswa masuk ke /login dan pengelola (admin/guru) ke /admin/login.
     Sebelumnya akar langsung dialihkan ke /login, sehingga guru & admin harus
     hafal alamat /admin/login.

     Halaman ini pula yang dibaca WhatsApp/Google saat tautan domain dibagikan,
     jadi meta lengkapnya dipasang lewat partials.head-meta. --}}
@php
    $namaSitus = $appSettings['site_name'] ?? 'CBT SYNC';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head-meta', [
        'metaTitle' => 'Menu Login',
        'metaDescription' => 'Pilih menu login ' . $namaSitus . ': portal siswa untuk mengikuti ujian berbasis komputer,'
            . ' atau menu pengelola untuk admin dan guru mengelola soal, jadwal, serta hasil dan nilai.',
        'metaRobots' => 'index, follow',
    ])
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Outfit:300,400,500,600,700" />
    <link href="{{ URL::to('assets/plugins/global/plugins.bundle.css') }}?v={{ filemtime(public_path('assets/plugins/global/plugins.bundle.css')) }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::to('assets/css/style.bundle.css') }}?v={{ filemtime(public_path('assets/css/style.bundle.css')) }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/keenicons-fix.css') }}?v={{ filemtime(public_path('assets/css/keenicons-fix.css')) }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::to('assets/css/elite-theme.css') }}?v={{ filemtime(public_path('assets/css/elite-theme.css')) }}" rel="stylesheet" type="text/css" />
    <style>
        /* Satu layar tanpa gulir; dvh dipakai supaya bilah alamat peramban ponsel
           tidak membuat halaman melebihi layar (masalah lama pada 100vh). */
        html, body { height: 100%; }
        body {
            margin: 0; min-height: 100dvh; display: flex; align-items: center; justify-content: center;
            font-family: 'Outfit', system-ui, sans-serif; padding: 20px; background: #f8fafc; color: #0f172a;
        }
        .latar {
            position: fixed; inset: 0; z-index: -1; background-color: #f8fafc;
            background-image:
                radial-gradient(at 0% 0%, hsla(220,100%,94%,1) 0, transparent 50%),
                radial-gradient(at 92% 8%, hsla(300,100%,95%,1) 0, transparent 45%),
                radial-gradient(at 50% 100%, hsla(260,100%,95%,1) 0, transparent 55%);
        }
        .kotak { width: 100%; max-width: 880px; }
        .merek { display: flex; align-items: center; justify-content: center; gap: 14px; margin-bottom: 6px; }
        .merek img { height: 46px; }
        .sapaan { text-align: center; margin-bottom: 30px; }
        .sapaan h1 { font-size: clamp(1.45rem, 4vw, 2.05rem); font-weight: 800; letter-spacing: -.02em; margin: 14px 0 6px; }
        .sapaan p { color: #64748b; margin: 0; font-size: clamp(.9rem, 2.4vw, 1rem); }

        .pilihan { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; }
        .kartu {
            display: block; text-decoration: none; color: inherit; background: #fff; border: 1px solid #e2e8f0;
            border-radius: 20px; padding: 26px 24px; transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
            box-shadow: 0 1px 2px rgba(15,23,42,.04);
        }
        .kartu:hover, .kartu:focus-visible {
            transform: translateY(-4px); border-color: #c7d2fe; box-shadow: 0 18px 40px rgba(79,70,229,.16); outline: none;
        }
        .ikon {
            width: 58px; height: 58px; border-radius: 17px; display: flex; align-items: center; justify-content: center;
            margin-bottom: 16px; color: #fff;
        }
        .ikon i { font-size: 27px; color: #fff; }
        .ikon-siswa { background: linear-gradient(135deg,#4F46E5,#7C3AED); }
        .ikon-kelola { background: linear-gradient(135deg,#0F172A,#334155); }
        .kartu h2 { font-size: 1.16rem; font-weight: 700; margin: 0 0 6px; }
        .kartu p { color: #64748b; font-size: .88rem; margin: 0 0 16px; line-height: 1.55; }
        .lanjut { font-weight: 600; font-size: .9rem; color: #4F46E5; display: inline-flex; align-items: center; gap: 7px; }
        .kartu-kelola .lanjut { color: #0f172a; }
        .kaki { text-align: center; color: #94a3b8; font-size: .8rem; margin-top: 26px; }

        @media (prefers-color-scheme: dark) {
            body { background: #0b1220; color: #e8ecf7; }
            .latar { background-color: #0b1220; background-image:
                radial-gradient(at 0% 0%, hsla(230,60%,20%,1) 0, transparent 50%),
                radial-gradient(at 92% 8%, hsla(300,50%,18%,1) 0, transparent 45%),
                radial-gradient(at 50% 100%, hsla(260,60%,18%,1) 0, transparent 55%); }
            .kartu { background: #111a2e; border-color: #1e293b; }
            .kartu:hover { border-color: #4338ca; }
            .kartu p, .sapaan p, .kaki { color: #94a3b8; }
            .kartu-kelola .lanjut { color: #c7d2fe; }
        }
    </style>
</head>
<body>
<div class="latar"></div>

<div class="kotak">
    <div class="sapaan">
        <div class="merek">
            <img src="{{ asset('assets/media/logos/cbt-logo.svg') }}" alt="{{ $namaSitus }}">
        </div>
        <h1>Menu Login {{ $namaSitus }}</h1>
        <p>Pilih menu sesuai peran Anda.</p>
    </div>

    <div class="pilihan">
        <a href="{{ route('student.login') }}" class="kartu kartu-siswa">
            <div class="ikon ikon-siswa"><i class="ki-outline ki-teacher"></i></div>
            <h2>Menu Siswa</h2>
            <p>Masuk memakai <b>username</b>, <b>email</b>, atau <b>NISN</b> beserta sandi pada kartu ujian.
               Untuk mengikuti ujian, melihat kartu ujian, jadwal, serta nilai.</p>
            <span class="lanjut">Masuk sebagai Siswa <i class="ki-outline ki-arrow-right fs-5"></i></span>
        </a>

        <a href="{{ route('login') }}" class="kartu kartu-kelola">
            <div class="ikon ikon-kelola"><i class="ki-outline ki-setting-2"></i></div>
            <h2>Menu Admin &amp; Guru</h2>
            <p>Untuk <b>admin</b>, <b>guru</b>, dan <b>kepala sekolah</b>. Mengelola soal, jadwal ujian,
               data siswa, daftar hadir, serta memeriksa hasil dan nilai.</p>
            <span class="lanjut">Masuk sebagai Pengelola <i class="ki-outline ki-arrow-right fs-5"></i></span>
        </a>
    </div>

    <div class="kaki">{{ date('Y') }} &copy; {{ $namaSitus }}</div>
</div>
</body>
</html>
