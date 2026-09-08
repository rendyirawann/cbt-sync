{{-- Head meta bersama (SEO). Sumber nilai: setting DB ($appSettings) -> config('seo.*') -> .env.

     Cara override, dua-duanya didukung:
       - @section('title'), @section('meta_description'), @section('meta_keywords'),
         @section('meta_robots')  -> untuk halaman yang memakai layout
       - @include('partials.head-meta', ['metaTitle' => '...', 'metaRobots' => '...'])
         -> untuk halaman berdiri sendiri yang tidak punya @extends

     CATATAN PENTING soal robots: bawaannya 'index, follow', jadi setiap layout
     yang isinya BUKAN untuk publik (portal siswa, panel admin) HARUS mengirim
     metaRobots 'noindex, nofollow'. Halaman login sengaja dibiarkan terindeks
     karena itulah alamat yang dibagikan ke siswa dan yang dibaca WhatsApp/
     Google saat tautannya ditempel.

     Tidak memuat <base>; biarkan layout yang punya <base> mengaturnya sendiri. --}}
@php
    $s        = $appSettings ?? [];
    $siteName = $s['site_name'] ?? config('seo.title');
    $pageT    = trim($__env->yieldContent('title'));
    $fullT    = ($pageT !== '' ? $pageT.' — ' : '').$siteName;
    $desc     = trim($__env->yieldContent('meta_description')) ?: ($s['site_description'] ?? config('seo.description'));
    $kw       = trim($__env->yieldContent('meta_keywords'))    ?: ($s['site_keywords']    ?? config('seo.keywords'));
    $ogImg    = $s['og_image'] ?? config('seo.og_image');
    $ogUrl    = \Illuminate\Support\Str::startsWith($ogImg, ['http://','https://']) ? $ogImg : asset(ltrim($ogImg, '/'));
    $fav      = !empty($s['site_logo']) ? 'assets/media/logos/'.$s['site_logo'] : config('seo.favicon');
    $tw       = config('seo.twitter');
    $theme    = config('seo.theme_color');
    // Variabel dari @include menang atas @section, dan @section menang atas config.
    $pageT    = trim($metaTitle ?? '') ?: $pageT;
    $fullT    = ($pageT !== '' ? $pageT.' — ' : '').$siteName;
    $desc     = trim($metaDescription ?? '') ?: $desc;
    $robots   = trim($metaRobots ?? '') ?: (trim($__env->yieldContent('meta_robots')) ?: 'index, follow');
    $terindeks = ! \Illuminate\Support\Str::contains($robots, 'noindex');
    $ogW      = (int) config('seo.og_image_width');
    $ogH      = (int) config('seo.og_image_height');
    $ogAlt    = config('seo.og_image_alt');
@endphp
<title>{{ $fullT }}</title>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
<meta name="description" content="{{ $desc }}" />
<meta name="keywords" content="{{ $kw }}" />
<meta name="theme-color" content="{{ $theme }}" />
<meta name="robots" content="{{ $robots }}" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<meta name="application-name" content="{{ $siteName }}" />
<meta name="apple-mobile-web-app-title" content="{{ $siteName }}" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
<meta name="format-detection" content="telephone=no" />
<link rel="canonical" href="{{ url()->current() }}" />
{{-- Open Graph --}}
<meta property="og:site_name" content="{{ $siteName }}" />
<meta property="og:locale" content="{{ config('seo.locale') }}" />
<meta property="og:type" content="website" />
<meta property="og:title" content="{{ $fullT }}" />
<meta property="og:description" content="{{ $desc }}" />
<meta property="og:url" content="{{ url()->current() }}" />
<meta property="og:image" content="{{ $ogUrl }}" />
<meta property="og:image:secure_url" content="{{ $ogUrl }}" />
<meta property="og:image:type" content="image/jpeg" />
{{-- Lebar & tinggi disertakan supaya WhatsApp/Facebook langsung menampilkan
     pratinjau besar tanpa harus mengunduh gambarnya lebih dulu. --}}
<meta property="og:image:width" content="{{ $ogW }}" />
<meta property="og:image:height" content="{{ $ogH }}" />
<meta property="og:image:alt" content="{{ $ogAlt }}" />
{{-- Twitter --}}
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $fullT }}" />
<meta name="twitter:description" content="{{ $desc }}" />
<meta name="twitter:image" content="{{ $ogUrl }}" />
<meta name="twitter:image:alt" content="{{ $ogAlt }}" />
@if($tw)<meta name="twitter:site" content="{{ $tw }}" />@endif
{{-- Ikon --}}
<link rel="icon" href="{{ asset($fav) }}" sizes="any" />
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/logos/favicon-32x32.png') }}" />
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/media/logos/favicon-16x16.png') }}" />
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/media/logos/apple-touch-icon.png') }}" />
<link rel="manifest" href="{{ asset('site.webmanifest') }}" />
{{-- Data terstruktur. Hanya diterbitkan pada halaman yang boleh diindeks —
     memasangnya di panel admin tidak ada gunanya dan hanya menambah berat.

     Susunannya dibangun di blok php lalu dicetak dengan json_encode, BUKAN
     dengan direktif json bawaan Blade: direktif itu membaca argumennya dengan
     mencocokkan tanda kurung, dan array multi-baris berisi kunci ber-tanda-at
     membuatnya berhenti di kurung yang salah -> ParseError
     "Unclosed '[' ... does not match ')'".

     Perhatikan juga: nama direktif Blade JANGAN ditulis lengkap dengan tanda
     at di dalam komentar seperti ini — Blade tetap mengompilasinya walau ada
     di dalam {{-- --}}, dan berkas ini pernah gagal parse karena itu. --}}
@if($terindeks)
    @php
        $ldJson = json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'WebSite',
                    '@id' => url('/') . '#website',
                    'name' => $siteName,
                    'url' => url('/'),
                    'description' => $desc,
                    'inLanguage' => 'id-ID',
                    'publisher' => ['@id' => url('/') . '#organization'],
                ],
                [
                    '@type' => 'EducationalOrganization',
                    '@id' => url('/') . '#organization',
                    'name' => $siteName,
                    'url' => url('/'),
                    'logo' => $ogUrl,
                ],
                [
                    '@type' => 'WebPage',
                    '@id' => url()->current(),
                    'url' => url()->current(),
                    'name' => $fullT,
                    'description' => $desc,
                    'isPartOf' => ['@id' => url('/') . '#website'],
                    'inLanguage' => 'id-ID',
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    @endphp
    <script type="application/ld+json">{!! $ldJson !!}</script>
@endif
