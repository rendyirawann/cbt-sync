@include('errors._layout', [
    'code' => 419,
    'title' => 'Sesi Kedaluwarsa',
    'message' => 'Halaman terlalu lama terbuka sehingga sesi keamanannya kedaluwarsa. '
        . 'Masuk lagi lalu lanjutkan — <b>jawaban ujian yang sudah Anda isi tetap tersimpan</b>, '
        . 'karena setiap jawaban disimpan saat dipilih, bukan menunggu tombol selesai.',
    'showLogin' => true,
    'showLogout' => true,
    'showReload' => true,
])
