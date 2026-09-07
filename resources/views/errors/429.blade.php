@include('errors._layout', [
    'code' => 429,
    'title' => 'Terlalu Banyak Percobaan',
    'message' => 'Permintaan dari perangkat ini terlalu sering dalam waktu singkat, jadi sementara ditahan. '
        . 'Ini biasanya muncul setelah beberapa kali salah password. Tunggu sekitar satu menit, lalu coba lagi.',
    'showReload' => true,
    'showBack' => false,
])
