@include('errors._layout', [
    'code' => 404,
    'title' => 'Halaman Tidak Ditemukan',
    'message' => 'Halaman yang Anda cari tidak ada, tautannya salah, atau datanya sudah tidak tersedia. '
        . 'Ujian yang <b>sudah selesai</b> juga tidak lagi terbuka untuk Admin, Guru, dan Siswa.',
    'illustration' => ['light' => 'assets/media/auth/404-error.png', 'dark' => 'assets/media/auth/404-error-dark.png'],
])
