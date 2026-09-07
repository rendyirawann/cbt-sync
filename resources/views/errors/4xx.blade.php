{{-- Penampung semua galat 4xx yang tidak punya berkas sendiri (mis. 405, 408, 413). --}}
@include('errors._layout', [
    'code' => ($exception ?? null) ? $exception->getStatusCode() : '4xx',
    'title' => 'Permintaan Tidak Dapat Diproses',
    'message' => 'Permintaan Anda tidak dapat dilayani. Muat ulang halaman, lalu coba lagi. '
        . 'Bila Anda mengunggah berkas, pastikan ukurannya tidak berlebihan.',
])
