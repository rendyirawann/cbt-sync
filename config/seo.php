<?php

return [
    // Judul situs (fallback ke APP_NAME). Bisa di-override per-halaman via @section('title').
    'title'       => env('SEO_TITLE', env('APP_NAME', 'CBT Sync')),
    'description' => env('SEO_DESCRIPTION', 'CBT SYNC — portal ujian berbasis komputer untuk sekolah: jadwal ujian, kartu ujian, daftar hadir peserta, serta hasil dan nilai dalam satu tempat.'),
    'keywords'    => env('SEO_KEYWORDS', 'CBT, ujian berbasis komputer, ujian online sekolah, kartu ujian, daftar hadir peserta, ANBK, portal ujian siswa'),
    'og_image'    => env('SEO_OG_IMAGE', 'og-image.jpg'),      // relatif ke public/
    // Ukuran og:image WAJIB cocok dengan berkasnya. Ditulis sebagai angka, bukan
    // dibaca dengan getimagesize(), supaya tiap permintaan halaman tidak
    // menyentuh disk hanya untuk dua angka yang tidak pernah berubah.
    'og_image_width'  => env('SEO_OG_IMAGE_WIDTH', 1200),
    'og_image_height' => env('SEO_OG_IMAGE_HEIGHT', 630),
    'og_image_alt'    => env('SEO_OG_IMAGE_ALT', 'CBT SYNC — portal ujian berbasis komputer untuk sekolah'),
    'twitter'     => env('SEO_TWITTER', ''),                    // mis. @handle (kosong => diabaikan)
    'locale'      => env('SEO_LOCALE', 'id_ID'),
    'favicon'     => env('SEO_FAVICON', 'assets/media/logos/favicon.ico'),
    'theme_color' => env('SEO_THEME_COLOR', '#4F46E5'),
];
