<!DOCTYPE html>
<html lang="id">
<head>
    {{-- Halaman INI yang dibaca ketika tautan domain dibagikan: akar domain
         dialihkan ke /login, jadi meta lengkapnya harus dipasang di sini —
         bukan hanya di layout portal yang baru muncul setelah siswa masuk.
         Sebelumnya halaman ini cuma punya <title>, itu sebabnya pratinjau di
         WhatsApp tampil polos tanpa keterangan dan tanpa gambar. --}}
    @include('partials.head-meta', [
        'metaTitle' => 'Login Siswa',
        'metaDescription' => 'Masuk ke portal ujian ' . ($appSettings['site_name'] ?? 'CBT SYNC')
            . ' memakai username, email, atau NISN untuk mengikuti ujian berbasis komputer,'
            . ' melihat kartu ujian dan jadwal, serta hasil dan nilai.',
        'metaRobots' => 'index, follow',
    ])
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Outfit:300,400,500,600,700" />
    <link href="{{ URL::to('assets/plugins/global/plugins.bundle.css') }}?v={{ filemtime(public_path('assets/plugins/global/plugins.bundle.css')) }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::to('assets/css/style.bundle.css') }}?v={{ filemtime(public_path('assets/css/style.bundle.css')) }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/keenicons-fix.css') }}?v={{ filemtime(public_path('assets/css/keenicons-fix.css')) }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::to('assets/css/elite-theme.css') }}?v={{ filemtime(public_path('assets/css/elite-theme.css')) }}" rel="stylesheet" type="text/css" />
    <style>
        :root { --pixel-size: 30px; }
        body { 
            font-family: 'Outfit', sans-serif;
            background-color: #f8fafc;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        /* Light Abstract Backgrounds */
        .abstract-mesh {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -2;
            background-color: #f8fafc;
            background-image: 
                radial-gradient(at 0% 0%, hsla(220,100%,95%,1) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(260,100%,95%,1) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(330,100%,95%,1) 0, transparent 50%), 
                radial-gradient(at 0% 100%, hsla(180,100%,95%,1) 0, transparent 50%), 
                radial-gradient(at 100% 100%, hsla(220,100%,95%,1) 0, transparent 50%);
        }
        .pixel-bg {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;
            background: 
                linear-gradient(45deg, #e2e8f0 25%, transparent 25%) -30px 0,
                linear-gradient(-45deg, #e2e8f0 25%, transparent 25%) -30px 0,
                linear-gradient(45deg, transparent 75%, #e2e8f0 75%),
                linear-gradient(-45deg, transparent 75%, #e2e8f0 75%);
            background-size: var(--pixel-size) var(--pixel-size);
            opacity: 0.4;
        }

        /* Split Layout Container */
        .auth-wrapper { height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .auth-container { 
            display: flex; width: 100%; max-width: 1100px; height: 700px;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 40px;
            overflow: hidden;
            box-shadow: 0 40px 80px -20px rgba(0, 0, 0, 0.08);
            animation: fadeInScale 0.8s ease-out;
        }

        @keyframes fadeInScale { from { opacity: 0; transform: scale(0.97); } to { opacity: 1; transform: scale(1); } }

        /* Left Side: Illustration */
        .auth-side-image {
            flex: 1; background: rgba(255, 255, 255, 0.3);
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            padding: 3rem; border-right: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
        }
        .floating-img { width: 100%; max-width: 380px; filter: drop-shadow(0 30px 40px rgba(0,0,0,0.1)); animation: float 6s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-15px); } }

        /* Right Side: Form */
        .auth-side-form { flex: 1; display: flex; align-items: center; justify-content: center; padding: 3rem; background: rgba(255, 255, 255, 0.2); }
        .login-form-wrapper { width: 100%; max-width: 380px; }

        .form-control-pixel {
            background: #ffffff !important; border: 1.5px solid #e2e8f0 !important;
            color: #1e293b !important; border-radius: 16px !important; height: 55px;
            font-weight: 500;
        }
        .form-control-pixel:focus {
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1) !important;
        }

        .btn-pixel {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            border: none; color: white; border-radius: 16px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1.5px; height: 55px; transition: all 0.3s ease;
        }
        .btn-pixel:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(99, 102, 241, 0.25); color: white; }

        .form-label { color: #475569 !important; }
        .text-gray-900 { color: #0f172a !important; }
        
        @media (max-width: 991px) {
            .auth-side-image { display: none; }
            .auth-container { max-width: 500px; height: auto; min-height: 500px; }
        }
    </style>
</head>
<body>
    <div class="abstract-mesh"></div>
    <div class="pixel-bg"></div>
    
    <div class="auth-wrapper">
        <div class="auth-container">
            <!-- Left Side -->
            <div class="auth-side-image">
                <img src="{{ URL::to('assets/media/logos/' . ($appSettings['site_logo'] ?? 'cbt-logo.svg')) }}" class="h-100px mb-12" alt="">
                <img src="{{ URL::to('assets/media/illustrations/doofenshmirtz/13.png') }}" class="floating-img mb-10" alt="">
                <div class="text-center px-5">
                    <h2 class="text-gray-900 fw-bolder mb-3 fs-1">Selamat Datang di {{ $appSettings['site_name'] ?? 'CBT-SYNC' }}</h2>
                    <p class="text-gray-600 fs-6">Masuk untuk mengikuti ujian online (CBT) sekolahmu.</p>
                </div>
            </div>

            <!-- Right Side -->
            <div class="auth-side-form">
                <div class="login-form-wrapper">
                    <div class="mb-10 text-center text-lg-start">
                        <h1 class="text-gray-900 fw-bolder mb-2 fs-2hx">Portal Siswa</h1>
                        <p class="text-gray-500 fw-semibold fs-6">Silakan login dengan akun yang terdaftar.</p>
                    </div>

                    <form class="form w-100" id="form_login_siswa">
                        <div class="fv-row mb-7">
                            <label class="form-label fs-8 fw-bolder text-uppercase ls-1">Email / Username / NISN</label>
                            <input type="text" placeholder="email, username, atau NISN" name="login" autocomplete="username" class="form-control form-control-pixel" required />
                            <div class="text-muted fs-8 mt-2">Bisa memakai salah satu: email sekolah, username pada kartu login, atau NISN.</div>
                        </div>

                        <div class="fv-row mb-10">
                            <div class="d-flex flex-stack mb-2">
                                <label class="form-label fs-8 fw-bolder text-uppercase ls-1 mb-0">Password</label>
                                <a href="#" class="link-primary fs-8 fw-bold" id="lupaSandiSiswa">Lupa Password?</a>
                            </div>
                            <input type="password" placeholder="••••••••" name="password" autocomplete="off" class="form-control form-control-pixel" required />
                        </div>

                        <div class="d-grid mb-8">
                            <button type="submit" id="btn_login" class="btn btn-pixel">
                                <span class="indicator-label">MASUK SEKARANG</span>
                                <span class="indicator-progress">
                                    <span class="spinner-border spinner-border-sm align-middle me-2"></span> Memproses...
                                </span>
                            </button>
                        </div>

                        <div class="text-center">
                            <span class="text-gray-600 fs-8 fw-bold">Butuh bantuan teknis?</span>
                            <a href="#" class="link-primary fs-8 fw-bolder ms-1">Hubungi Guru</a>
                        </div>

                        {{-- Tautan silang: guru/admin yang salah membuka halaman ini tidak
                             perlu mengetik ulang alamat /admin/login. --}}
                        <div class="separator separator-content my-6"><span class="text-gray-500 fs-8 fw-semibold">Bukan siswa?</span></div>
                        <div class="text-center">
                            <a href="{{ route('login') }}" class="btn btn-sm btn-light-dark fw-bold">
                                <i class="ki-outline ki-setting-2 fs-5"></i> Masuk sebagai Admin / Guru
                            </a>
                            <div class="mt-3"><a href="{{ route('landing') }}" class="link-primary fs-8 fw-semibold">Kembali ke Menu Login</a></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ URL::to('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ URL::to('assets/js/scripts.bundle.js') }}"></script>
    
    <script>
        $(document).ready(function() {
            $('#form_login_siswa').on('submit', function(e) {
                e.preventDefault();
                const btn = $('#btn_login');
                btn.attr('data-kt-indicator', 'on').prop('disabled', true);

                $.ajax({
                    url: "{{ route('student.authenticate') }}",
                    method: "POST",
                    data: $(this).serialize(),
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function(res) {
                        Swal.fire({
                            text: res.message, icon: "success",
                            buttonsStyling: false,
                            confirmButtonText: "Ke Dashboard",
                            customClass: { confirmButton: "btn btn-primary" }
                        }).then(() => { window.location.href = res.redirect; });
                    },
                    error: function(xhr) {
                        btn.removeAttr('data-kt-indicator').prop('disabled', false);

                        // 419 = token CSRF kedaluwarsa, bukan salah sandi. Menampilkan
                        // "Email/password salah" di sini menyesatkan, dan mencoba lagi
                        // tidak akan pernah berhasil karena tokennya tetap basi.
                        // Halamannya dimuat ulang supaya token baru terpasang.
                        if (xhr.status === 419) {
                            Swal.fire({
                                text: "Sesi keamanan kedaluwarsa karena halaman dibiarkan terbuka terlalu lama. Halaman akan dimuat ulang.",
                                icon: "info",
                                buttonsStyling: false,
                                confirmButtonText: "Muat Ulang",
                                customClass: { confirmButton: "btn btn-primary" }
                            }).then(function () { window.location.reload(); });
                            return;
                        }

                        Swal.fire({
                            text: xhr.responseJSON?.message || "Email/Username/NISN atau password salah!",
                            icon: "error",
                            buttonsStyling: false,
                            confirmButtonText: "Coba Lagi",
                            customClass: { confirmButton: "btn btn-danger" }
                        });
                    }
                });
            });
        });
    </script>
@include('partials.dev-credit')
</body>
</html>

<script>
    // Lupa sandi TIDAK dibuat mandiri (tanpa email reset) dan itu disengaja:
    // sandi siswa tercetak di Kartu Ujian dan diterbitkan oleh admin/guru, jadi
    // jalur pemulihannya memang lewat orang — bukan lewat surel siswa, yang
    // banyak di antaranya bahkan memakai alamat buatan sekolah.
    //
    // Pendengarnya dipasang pada document (bukan pada elemennya langsung) supaya
    // tidak bergantung pada urutan pemuatan skrip di halaman ini.
    document.addEventListener('click', function (e) {
        var a = e.target.closest('#lupaSandiSiswa');
        if (!a) return;
        e.preventDefault();

        Swal.fire({
            title: 'Lupa Password?',
            html: '<div class="text-start fs-6">'
                + 'Password siswa <b>tidak bisa direset sendiri</b>.'
                + '<div class="mt-3">Silakan <b>hubungi admin atau guru</b> di sekolahmu.</div>'
                + '<div class="text-muted fs-7 mt-3">Password kamu tercetak di <b>Kartu Ujian</b>. '
                + 'Bila kartunya hilang, admin/guru bisa menerbitkan kartu baru beserta password barunya.</div>'
                + '</div>',
            icon: 'info',
            confirmButtonText: 'Mengerti',
            customClass: { confirmButton: 'btn btn-primary' },
            buttonsStyling: false
        });
    });
</script>
