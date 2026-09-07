{{-- Halaman pemeliharaan (Toggle Maintenance di Settings).
     Memakai kerangka errors._layout yang sama seperti galat lain; versi lamanya
     berdiri sendiri tanpa menyetel data-bs-theme, sehingga kedua varian
     ilustrasi ikut tampil dan halamannya jadi memanjang. --}}
@include('errors._layout', [
    'code' => false,   {{-- tanpa angka: ini pemeliharaan terencana, bukan galat --}}
    'title' => 'Sedang Dalam Pemeliharaan',
    'message' => 'Sistem ujian sedang diperbarui, jadi belum bisa diakses untuk sementara. '
        . 'Mohon kembali beberapa saat lagi. Terima kasih atas kesabarannya.',
    'illustration' => ['light' => 'assets/media/auth/maintenance.png', 'dark' => 'assets/media/auth/maintenance-dark.png'],
    'showHome' => false,
    'showBack' => false,
    'showReload' => true,
    // Akun yang sudah masuk diberi tombol Keluar; pengunjung yang belum masuk
    // diberi tombol Masuk (login memang tetap dibuka saat pemeliharaan).
    'showLogout' => true,
    'showLogin' => true,
])
