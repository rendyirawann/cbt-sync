<?php

return [
    // Judul situs (fallback ke APP_NAME). Bisa di-override per-halaman via @section('title').
    'title'       => env('SEO_TITLE', env('APP_NAME', 'CBT Sync')),
    'description' => env('SEO_DESCRIPTION', 'Platform Belajar & Ujian Online (CBT) untuk sekolah, bimbel, dan homeschooling.'),
    'keywords'    => env('SEO_KEYWORDS', 'LMS, CBT, ujian online, e-learning, sekolah, bimbel, homeschool'),
    'og_image'    => env('SEO_OG_IMAGE', 'og-image.jpg'),      // relatif ke public/
    'twitter'     => env('SEO_TWITTER', ''),                    // mis. @handle (kosong => diabaikan)
    'locale'      => env('SEO_LOCALE', 'id_ID'),
    'favicon'     => env('SEO_FAVICON', 'assets/media/logos/favicon.ico'),
    'theme_color' => env('SEO_THEME_COLOR', '#4F46E5'),
];
