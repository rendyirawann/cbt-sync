@include('errors._layout', [
    'code' => 500,
    'title' => 'Terjadi Kesalahan',
    'message' => 'Ada masalah di server kami. Tim kami sedang menanganinya — silakan coba beberapa saat lagi.',
    'illustration' => ['light' => 'assets/media/auth/500-error.png', 'dark' => 'assets/media/auth/500-error-dark.png'],
])
