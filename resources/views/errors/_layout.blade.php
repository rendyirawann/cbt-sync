@php
    $siteName = $appSettings['site_name'] ?? config('seo.title', config('app.name', 'CBT Sync'));
    $logo     = 'assets/media/logos/' . ($appSettings['site_logo'] ?? 'base-logo.png');
    $code     = $code ?? 500;
    $title    = $title ?? 'Terjadi Kesalahan';
    $message  = $message ?? 'Maaf, terjadi kesalahan yang tidak terduga.';
    $illustration = $illustration ?? null;   // path relatif di public/, opsional
    $showLogin = $showLogin ?? false;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <title>{{ $code }} — {{ $title }} | {{ $siteName }}</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
    <script>
        // Selaraskan tema dengan pilihan pengguna di aplikasi (Metronic).
        (function () {
            try {
                var m = localStorage.getItem('kt_theme_mode_value') || 'system';
                if (m === 'system') {
                    m = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }
                document.documentElement.setAttribute('data-bs-theme', m);
            } catch (e) {}
        })();
    </script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" />
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <style>
        body {
            background-image: url('{{ asset('assets/media/auth/bg9.jpg') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        [data-bs-theme="dark"] body {
            background-image: url('{{ asset('assets/media/auth/bg9-dark.jpg') }}');
        }
        .err-code {
            font-weight: 800;
            font-size: clamp(88px, 17vw, 170px);
            line-height: 1;
            letter-spacing: -.03em;
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 55%, #DB2777 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            color: transparent;
            margin: 0;
        }
    </style>
</head>
<body id="kt_body" class="app-blank bgi-size-cover bgi-position-center bgi-no-repeat">
    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <div class="d-flex flex-column flex-center flex-column-fluid p-6">
            <div class="card card-flush w-lg-650px py-5 shadow-sm">
                <div class="card-body py-12 py-lg-16 text-center">

                    {{-- Logo --}}
                    <div class="mb-8">
                        <a href="{{ url('/') }}">
                            <img alt="{{ $siteName }}" src="{{ asset($logo) }}" class="h-45px" />
                        </a>
                    </div>

                    {{-- Kode error --}}
                    <h1 class="err-code mb-2">{{ $code }}</h1>

                    {{-- Judul & pesan --}}
                    <h2 class="fw-bold text-gray-900 mb-3 fs-1">{{ $title }}</h2>
                    <div class="fw-semibold fs-5 text-gray-600 mb-8 mx-auto" style="max-width:460px">
                        {!! $message !!}
                    </div>

                    {{-- Ilustrasi opsional --}}
                    @if($illustration)
                    <div class="mb-10">
                        <img src="{{ asset($illustration['light']) }}" class="mw-100 mh-220px theme-light-show" alt="" />
                        <img src="{{ asset($illustration['dark']) }}" class="mw-100 mh-220px theme-dark-show" alt="" />
                    </div>
                    @endif

                    {{-- Aksi --}}
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="{{ url('/') }}" class="btn btn-primary">
                            <i class="ki-outline ki-home-2 fs-4"></i> Ke Beranda
                        </a>
                        <a href="javascript:history.back()" class="btn btn-light">
                            <i class="ki-outline ki-arrow-left fs-4"></i> Halaman Sebelumnya
                        </a>
                        @if($showLogin && Route::has('login'))
                        <a href="{{ route('login') }}" class="btn btn-light-primary">
                            <i class="ki-outline ki-entrance-right fs-4"></i> Masuk
                        </a>
                        @endif
                    </div>

                </div>
            </div>
            <div class="text-gray-500 fs-7 mt-6">{{ date('Y') }} © {{ $siteName }}</div>
        </div>
    </div>
</body>
</html>
