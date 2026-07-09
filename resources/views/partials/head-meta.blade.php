{{-- Head meta bersama (SEO). Sumber nilai: setting DB ($appSettings) -> config('seo.*') -> .env.
     Per-halaman bisa override lewat @section('title'), @section('meta_description'), @section('meta_keywords').
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
@endphp
<title>{{ $fullT }}</title>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
<meta name="description" content="{{ $desc }}" />
<meta name="keywords" content="{{ $kw }}" />
<meta name="theme-color" content="{{ $theme }}" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
<link rel="canonical" href="{{ url()->current() }}" />
{{-- Open Graph --}}
<meta property="og:site_name" content="{{ $siteName }}" />
<meta property="og:locale" content="{{ config('seo.locale') }}" />
<meta property="og:type" content="website" />
<meta property="og:title" content="{{ $fullT }}" />
<meta property="og:description" content="{{ $desc }}" />
<meta property="og:url" content="{{ url()->current() }}" />
<meta property="og:image" content="{{ $ogUrl }}" />
{{-- Twitter --}}
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $fullT }}" />
<meta name="twitter:description" content="{{ $desc }}" />
<meta name="twitter:image" content="{{ $ogUrl }}" />
@if($tw)<meta name="twitter:site" content="{{ $tw }}" />@endif
{{-- Ikon --}}
<link rel="icon" href="{{ asset($fav) }}" sizes="any" />
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/media/logos/favicon-32x32.png') }}" />
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/media/logos/favicon-16x16.png') }}" />
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/media/logos/apple-touch-icon.png') }}" />
<link rel="manifest" href="{{ asset('site.webmanifest') }}" />
