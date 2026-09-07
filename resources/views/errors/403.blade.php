@include('errors._layout', [
    'code' => 403,
    'title' => 'Akses Ditolak',
    'message' => (isset($exception) && $exception->getMessage())
        ? e($exception->getMessage())
        : 'Peran akun Anda tidak berhak membuka halaman ini. Beberapa bagian memang dibatasi — '
          . 'mis. hasil ujian yang sudah diarsipkan hanya dapat dibuka Superadmin dan Developer.',
    'showLogin' => true,
    'showLogout' => true,
])
