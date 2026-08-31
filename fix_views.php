<?php
$files = [
    'resources/views/backend/master/academic-years/index.blade.php',
    'resources/views/backend/master/assignments/index.blade.php',
    'resources/views/backend/master/class-rooms/index.blade.php',
    'resources/views/backend/master/enrollments/index.blade.php',
    'resources/views/backend/master/learning-modules/index.blade.php',
    'resources/views/backend/master/schools/index.blade.php',
    'resources/views/backend/master/students/index.blade.php',
    'resources/views/backend/master/subjects/index.blade.php',
    'resources/views/backend/master/teachers/index.blade.php',
    'resources/views/backend/master/teaching-assignments/index.blade.php',
];

$sweetAlertScript = <<<HTML

@push('scripts')
<script>
    document.querySelectorAll('.confirm-delete').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endpush

@endsection
HTML;

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (!file_exists($path)) continue;
    
    $content = file_get_contents($path);
    
    // 1. Add Card Header if missing (Assignments, Learning Modules, Schedules are already fixed)
    if (strpos($content, 'class="card-header') === false) {
        // Extract Title from @section('title', 'XYZ')
        preg_match('/@section\(\'title\',\s*\'([^\']+)\'\)/', $content, $matches);
        $title = $matches[1] ?? 'Data';
        
        $headerHtml = <<<HTML
            <div class="card-header mt-5 border-0 pt-6">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">Daftar $title</h3>
                </div>
            </div>
            <div class="card-body
HTML;
        $content = str_replace('<div class="card-body', $headerHtml, $content);
    }
    
    // 2. Replace return confirm with confirm-delete
    // Example: onclick="return confirm('...')"
    $content = preg_replace('/onclick="return confirm\([^)]+\)"/', '', $content);
    $content = preg_replace('/onclick=\'return confirm\([^\)]+\)\'/', '', $content);
    
    // Also make sure the button has the class confirm-delete
    // Match: <button type="submit" class="btn btn-sm btn-light-danger btn-active-danger" >Hapus</button>
    // Sometimes it's <button ... class="...
    $content = preg_replace('/(<button type="submit"[^>]*class="[^"]*)(")/', '$1 confirm-delete$2', $content);
    
    // 3. Append SweetAlert script if not exists
    if (strpos($content, '.confirm-delete') === false && strpos($content, 'Swal.fire') === false) {
        // Remove the existing @endsection at the very end and append our block which includes @endsection
        $content = preg_replace('/@endsection\s*$/', $sweetAlertScript, $content);
    }

    file_put_contents($path, $content);
}

echo "Done replacing confirming scripts and headers.\n";
