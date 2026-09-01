@include('errors._layout', [
    'code' => 403,
    'title' => 'Akses Ditolak',
    'message' => (isset($exception) && $exception->getMessage()) ? e($exception->getMessage()) : 'Anda tidak memiliki izin untuk membuka halaman ini.',
    'showLogin' => true,
])
