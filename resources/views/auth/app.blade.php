<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->

<head>

    <title>@yield('title') — {{ $appSettings['site_name'] ?? 'CBT-SYNC' }}</title>
    <meta charset="utf-8" />
    <meta name="description" content="{{ $appSettings['site_name'] ?? 'CBT-SYNC' }} — Authentication" />
    <meta name="author" content="Rendy Irawan" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta property="og:locale" content="id_ID" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="{{ $appSettings['site_name'] ?? 'CBT-SYNC' }} — Login" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:site_name" content="{{ $appSettings['site_name'] ?? 'CBT-SYNC' }}" />
    <link rel="canonical" href="{{ url()->current() }}" />
    @php
        $siteLogo = $appSettings['site_logo'] ?? 'cbt-logo.svg';
        $siteFont = $appSettings['site_font'] ?? 'Plus Jakarta Sans';
        $siteName = $appSettings['site_name'] ?? 'CBT-SYNC';
    @endphp
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/' . $siteLogo) }}" />
    <!--begin::Fonts-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $siteFont) }}:wght@300;400;500;600;700;800&display=swap" />
    <!--end::Fonts-->
    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/elite-theme.css') }}?v=2" rel="stylesheet" type="text/css" />
    <!--end::Global Stylesheets Bundle-->
    <style>
        :root {
            --bs-font-sans-serif: '{{ $siteFont }}', sans-serif;
            --bs-body-font-family: '{{ $siteFont }}', sans-serif;
        }
        body { 
            font-family: '{{ $siteFont }}', sans-serif !important; 
        }
        h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {
            font-family: '{{ $siteFont }}', sans-serif !important;
        }
    </style>
    <script>
        // Frame-busting to prevent site from being loaded within a frame without permission (click-jacking)
        if (window.top != window.self) {
            window.top.location.replace(window.self.location.href);
        }
    </script>

    @stack('stylesheets')
</head>
<!--end::Head-->
<!--begin::Body-->

<body id="kt_body" class="app-blank bgi-size-cover bgi-attachment-fixed bgi-position-center">
    <!--begin::Theme mode setup on page load-->
    <script>
        var defaultThemeMode = "light";
        var themeMode;
        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else {
                if (localStorage.getItem("data-bs-theme") !== null) {
                    themeMode = localStorage.getItem("data-bs-theme");
                } else {
                    themeMode = defaultThemeMode;
                }
            }
            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }
            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }
    </script>
    <!--end::Theme mode setup on page load-->
    <!--begin::Root-->
    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <!--begin::Page bg image-->
        <style>
            body {
                background-image: url('{{ asset('assets/media/patterns/circuit-board.svg') }}');
            }

            [data-bs-theme="dark"] body {
                background-image: url('{{ asset('assets/media/auth/bg10-dark.jpeg') }}');
            }
        </style>
        <!--end::Page bg image-->
        <!--begin::Authentication - Sign-in -->
        <div class="d-flex flex-column flex-lg-row flex-column-fluid">
            <!--begin::Body-->
            @yield('content')
            <!--end::Body-->
            <!--begin::Aside (Panel Administrasi — simpel, beda dari portal siswa) -->
            <div class="d-none d-lg-flex flex-lg-row-fluid position-relative"
                 style="margin:18px 18px 18px 0;border-radius:28px;overflow:hidden;
                        background:linear-gradient(140deg,#0B1F3A 0%,#142C52 55%,#4F46E5 100%)">
                {{-- dekorasi lembut --}}
                <div style="position:absolute;right:-80px;top:-80px;width:280px;height:280px;border-radius:50%;background:radial-gradient(circle,rgba(244,196,48,.18),transparent 70%)"></div>
                <div style="position:absolute;left:-60px;bottom:-60px;width:240px;height:240px;border-radius:50%;background:radial-gradient(circle,rgba(124,58,237,.30),transparent 70%)"></div>

                <div class="d-flex flex-column flex-center p-15 w-100 position-relative" style="z-index:2">
                    {{-- logo kecil dalam badge putih --}}
                    <div class="d-flex align-items-center justify-content-center bg-white shadow mb-7"
                         style="width:88px;height:88px;border-radius:24px">
                        <img src="{{ asset('assets/media/logos/' . $siteLogo) }}" alt="Logo"
                             style="width:58px;height:58px;object-fit:contain">
                    </div>

                    <span class="elite-chip mb-4"><i class="ki-duotone ki-shield-tick fs-5 text-white"><span class="path1"></span><span class="path2"></span></i> Panel Administrasi</span>
                    <h1 class="text-white fw-bold text-center mb-3" style="font-size:clamp(24px,2.4vw,38px)">{{ $siteName }}</h1>
                    <p class="text-white text-center mb-9" style="max-width:400px;opacity:.75">Kelola sekolah, pembelajaran, ujian online (CBT), dan Raport Hasil Ujian dalam satu sistem terpadu.</p>

                    <div class="d-flex flex-column gap-4" style="width:100%;max-width:330px">
                        @foreach([['ki-data','Manajemen data sekolah & pengguna'],['ki-note-2','Ujian online (CBT) & penilaian'],['ki-chart-line-up','Analitik & laporan Raport Hasil Ujian']] as $f)
                        <div class="d-flex align-items-center text-white">
                            <span class="d-flex align-items-center justify-content-center me-3 flex-shrink-0"
                                  style="width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,.14)">
                                <i class="ki-duotone {{ $f[0] }} fs-3 text-white"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                            </span>
                            <span class="fw-semibold" style="opacity:.92">{{ $f[1] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <!--end::Aside-->
        </div>
        <!--end::Authentication - Sign-in-->
    </div>
    <!--end::Root-->
    <!--begin::Javascript-->

    <!--begin::Global Javascript Bundle(mandatory for all pages)-->
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    <!--end::Global Javascript Bundle-->
    @stack('scripts')
    @include('partials.dev-credit')
    <!--end::Javascript-->
</body>
<!--end::Body-->

</html>
