@include('errors._layout', [
    'code' => 401,
    'title' => 'Belum Masuk',
    'message' => 'Anda perlu masuk lebih dulu. <b>Siswa</b> masuk lewat halaman utama memakai '
        . 'email, username, atau NISN dari kartu ujian; <b>guru dan admin</b> lewat halaman login admin.',
    'showLogin' => true,
    'showLogout' => true,
])
