@include('errors._layout', [
    'code' => 404,
    'title' => 'Halaman Tidak Ditemukan',
    'message' => 'Halaman yang Anda cari tidak ada, sudah dipindahkan, atau tautannya salah.',
    'illustration' => ['light' => 'assets/media/auth/404-error.png', 'dark' => 'assets/media/auth/404-error-dark.png'],
])
