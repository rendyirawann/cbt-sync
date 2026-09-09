{{--
    Kerangka halaman galat CBT-SYNC.

    Dipakai semua berkas errors/*.blade.php supaya tampilannya satu rupa.

    Catatan penting soal tema: kelas Metronic `theme-light-show`/`theme-dark-show`
    hanya MENYEMBUNYIKAN (`[data-bs-theme=light] .theme-dark-show{display:none}`).
    Kalau atribut `data-bs-theme` tidak ada di <html>, kedua varian gambar ikut
    tampil — itu yang dulu membuat halaman pemeliharaan memuat dua ilustrasi dan
    jadi memanjang. Karena itu skrip di bawah SELALU menyetel atribut tersebut,
    termasuk saat localStorage tidak bisa dibaca.

    Parameter:
      $code          kode HTTP yang ditampilkan besar (boleh dikosongkan)
      $title         judul
      $message       kalimat penjelas (boleh berisi HTML)
      $illustration  ['light' => path, 'dark' => path] relatif ke public/
      $showLogin     tampilkan tombol Masuk
      $showHome      tampilkan tombol Ke Beranda (bawaan: ya)
      $showBack      tampilkan tombol Halaman Sebelumnya (bawaan: ya)
      $showReload    tampilkan tombol Coba Lagi
      $showLogout    tampilkan tombol Keluar bila akun sedang masuk
--}}
@php
    $siteName = $appSettings['site_name'] ?? config('seo.title', config('app.name', 'CBT Sync'));
    $logo     = 'assets/media/logos/' . ($appSettings['site_logo'] ?? 'cbt-logo.svg');
    // Kirim `false` (BUKAN null) untuk menyembunyikan angka kode — null
    // dianggap "tidak diisi" oleh ?? sehingga berubah menjadi 500.
    $code     = $code ?? 500;
    $title    = $title ?? 'Terjadi Kesalahan';
    $message  = $message ?? 'Maaf, terjadi kesalahan yang tidak terduga.';
    $illustration = $illustration ?? null;
    $showLogin  = $showLogin ?? false;
    $showHome   = $showHome ?? true;
    $showBack   = $showBack ?? true;
    $showReload = $showReload ?? false;
    $showLogout = $showLogout ?? false;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <title>{{ $code ? $code . ' — ' : '' }}{{ $title }} | {{ $siteName }}</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    {{-- Halaman galat memakai layoutnya sendiri dan TIDAK memuat
         partials/head-meta, jadi ikonnya harus ditentukan di sini. Sebelumnya
         menunjuk favicon.ico bawaan Metronic — itu salah satu sebab ikonnya
         terlihat berubah-ubah antar halaman. --}}
    @php $logoGalat = \App\Models\Setting::get('site_logo'); @endphp
    <link rel="icon" href="{{ $logoGalat ? asset('assets/media/logos/'.$logoGalat) : asset('favicon.ico') }}" sizes="any" />
    <script>
        // data-bs-theme WAJIB terpasang (lihat catatan di atas). Kalau pilihan
        // pengguna tidak bisa dibaca, jatuh ke preferensi sistem, lalu ke light.
        (function () {
            var m = 'light';
            try {
                m = localStorage.getItem('kt_theme_mode_value') || 'system';
            } catch (e) {
                m = 'system';
            }
            if (m === 'system') {
                try {
                    m = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                } catch (e) { m = 'light'; }
            }
            document.documentElement.setAttribute('data-bs-theme', m);
        })();
    </script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" />
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}?v={{ filemtime(public_path('assets/plugins/global/plugins.bundle.css')) }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}?v={{ filemtime(public_path('assets/css/style.bundle.css')) }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/keenicons-fix.css') }}?v={{ filemtime(public_path('assets/css/keenicons-fix.css')) }}" rel="stylesheet" type="text/css" />
    <style>
        /* Satu layar penuh tanpa gulir: tinggi diukur dengan dvh supaya bilah
           alamat browser ponsel tidak membuat halaman melebihi layar. */
        html, body { height: 100%; }
        body {
            margin: 0;
            background-image: url('{{ asset('assets/media/auth/bg9.jpg') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        [data-bs-theme="dark"] body {
            background-image: url('{{ asset('assets/media/auth/bg9-dark.jpg') }}');
        }

        .err-root {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: clamp(12px, 3vh, 32px) 16px;
            box-sizing: border-box;
        }
        .err-card {
            width: 100%;
            max-width: 620px;
            /* Kartu tidak boleh lebih tinggi dari layar; kalau isinya benar-benar
               tidak muat (layar sangat pendek), yang bergulir hanya kartunya. */
            max-height: calc(100dvh - 2 * clamp(12px, 3vh, 32px) - 34px);
            overflow-y: auto;
        }
        .err-card .card-body { padding: clamp(20px, 4.5vh, 44px) clamp(18px, 4vw, 44px); }

        .err-logo { height: clamp(30px, 5.5vh, 46px); width: auto; }

        .err-code {
            font-weight: 800;
            font-size: clamp(52px, 11vh, 132px);
            line-height: 1;
            letter-spacing: -.03em;
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 55%, #DB2777 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
            margin: 0 0 .25rem;
        }
        .err-title { font-size: clamp(19px, 3vh, 30px); }
        .err-msg   { font-size: clamp(13px, 1.9vh, 16px); max-width: 460px; margin-inline: auto; }

        /* Ilustrasi ikut mengecil bersama tinggi layar, dan disingkirkan sama
           sekali pada layar pendek supaya tidak memaksa gulir. */
        .err-illus img { max-width: 100%; height: auto; max-height: min(24vh, 190px); }
        @media (max-height: 620px) { .err-illus { display: none !important; } }

        .err-foot { font-size: 12px; margin-top: 10px; }
    </style>
</head>
<body id="kt_body" class="app-blank">
    <div class="err-root">
        <div class="card card-flush shadow-sm err-card">
            <div class="card-body text-center">

                <div class="mb-5">
                    <a href="{{ url('/') }}"><img alt="{{ $siteName }}" src="{{ asset($logo) }}" class="err-logo" /></a>
                </div>

                @if($code)
                    <h1 class="err-code">{{ $code }}</h1>
                @endif

                <h2 class="fw-bold text-gray-900 mb-3 err-title">{{ $title }}</h2>
                <div class="fw-semibold text-gray-600 mb-6 err-msg">{!! $message !!}</div>

                @if($illustration)
                    <div class="err-illus mb-6">
                        <img src="{{ asset($illustration['light']) }}" class="theme-light-show" alt="" />
                        <img src="{{ asset($illustration['dark']) }}" class="theme-dark-show" alt="" />
                    </div>
                @endif

                <div class="d-flex flex-wrap justify-content-center gap-2">
                    @if($showReload)
                        <a href="{{ url()->current() }}" class="btn btn-sm btn-primary">
                            <i class="ki-outline ki-arrows-circle fs-5"></i> Coba Lagi
                        </a>
                    @endif
                    @if($showHome)
                        <a href="{{ url('/') }}" class="btn btn-sm btn-{{ $showReload ? 'light' : 'primary' }}">
                            <i class="ki-outline ki-home-2 fs-5"></i> Ke Beranda
                        </a>
                    @endif
                    @if($showBack)
                        <a href="javascript:history.back()" class="btn btn-sm btn-light">
                            <i class="ki-outline ki-arrow-left fs-5"></i> Halaman Sebelumnya
                        </a>
                    @endif
                    {{-- Saat pemeliharaan, akun yang sudah masuk tidak bisa berbuat apa pun:
                         yang berguna baginya adalah KELUAR, bukan masuk lagi. Tombol Masuk
                         hanya ditawarkan kepada pengunjung yang belum masuk. --}}
                    @if($showLogout && auth()->check() && Route::has('logout'))
                        <form action="{{ route('logout') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-light-danger">
                                <i class="ki-outline ki-exit-right fs-5"></i> Keluar
                            </button>
                        </form>
                    @elseif($showLogin && Route::has('login') && ! auth()->check())
                        <a href="{{ route('login') }}" class="btn btn-sm btn-light-primary">
                            <i class="ki-outline ki-entrance-right fs-5"></i> Masuk
                        </a>
                    @endif
                </div>

            </div>
        </div>
        <div class="text-gray-500 err-foot">{{ date('Y') }} © {{ $siteName }}</div>
    </div>
</body>
</html>
