{{-- Penampung semua galat 5xx yang tidak punya berkas sendiri (mis. 502, 504). --}}
@include('errors._layout', [
    'code' => ($exception ?? null) ? $exception->getStatusCode() : '5xx',
    'title' => 'Sistem Sedang Bermasalah',
    'message' => 'Server tidak dapat menyelesaikan permintaan ini. '
        . 'Jawaban ujian yang sudah terisi tetap tersimpan. Coba lagi beberapa saat, '
        . 'atau hubungi proktor sekolah bila terus berulang.',
    'showReload' => true,
])
