<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi Lisensi - {{ config('app.name', 'CBT-SYNC') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Fallback if Vite is not running */
        body { font-family: system-ui, -apple-system, sans-serif; background-color: #f3f4f6; margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: white; padding: 2rem; border-radius: 0.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); width: 100%; max-width: 28rem; text-align: center; }
        .input-key { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; margin-top: 1rem; margin-bottom: 1rem; font-size: 1rem; text-align: center; font-weight: bold; letter-spacing: 0.05em; box-sizing: border-box; }
        .btn-submit { background-color: #4f46e5; color: white; padding: 0.75rem 1.5rem; border: none; border-radius: 0.375rem; font-size: 1rem; cursor: pointer; width: 100%; font-weight: 600; }
        .btn-submit:hover { background-color: #4338ca; }
        .alert-error { background-color: #fee2e2; color: #b91c1c; padding: 0.75rem; border-radius: 0.375rem; margin-bottom: 1rem; font-size: 0.875rem; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="card bg-white p-8 rounded-xl shadow-lg w-full max-w-md mx-4">
        
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Aktivasi Lisensi</h1>
            <p class="text-sm text-gray-500">Aplikasi Anda saat ini terkunci. Silakan masukkan kode lisensi yang valid untuk melanjutkan.</p>
        </div>

        @if(session('error'))
            <div class="alert-error bg-red-100 text-red-700 p-3 rounded mb-4 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('license.activate') }}" method="POST">
            @csrf
            <div>
                <input type="text" 
                       name="license_key" 
                       class="input-key w-full p-3 border border-gray-300 rounded focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-center font-mono font-bold tracking-widest uppercase mb-4" 
                       placeholder="XXXX-XXXX-XXXX-XXXX" 
                       required 
                       autofocus>
                
                @error('license_key')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-submit w-full bg-indigo-600 text-white p-3 rounded font-semibold hover:bg-indigo-700 transition">
                Aktivasi Aplikasi
            </button>
        </form>

        <div class="mt-6 text-xs text-gray-400">
            &copy; {{ date('Y') }} {{ config('app.name', 'CBT-SYNC') }}. All rights reserved.
        </div>
    </div>

</body>
</html>
