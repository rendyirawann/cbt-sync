@include('errors._layout', [
    'code' => 400,
    'title' => 'Permintaan Tidak Valid',
    'message' => 'Data yang dikirim tidak dapat dibaca sistem. Muat ulang halaman, lalu ulangi lagi. '
        . 'Kalau ini terjadi saat mengunggah foto jawaban, coba pakai foto lain.',
])
