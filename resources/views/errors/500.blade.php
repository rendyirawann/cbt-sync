@include('errors._layout', [
    'code' => 500,
    'title' => 'Terjadi Kesalahan Sistem',
    'message' => 'Ada masalah di server kami dan kejadian ini sudah dicatat. '
        . 'Bila Anda sedang mengerjakan ujian, <b>jawaban yang sudah terisi tetap tersimpan</b> — '
        . 'muat ulang halaman ujian dan lanjutkan. Kalau berulang, hubungi proktor sekolah.',
    'illustration' => ['light' => 'assets/media/auth/500-error.png', 'dark' => 'assets/media/auth/500-error-dark.png'],
    'showReload' => true,
])
