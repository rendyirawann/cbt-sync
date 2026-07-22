@section('title', 'Platform Belajar & Ujian Online (CBT) untuk Sekolah, Bimbel & Homeschooling')
@section('meta_description', 'LMS & CBT modern: kelola materi, tugas, absensi, Raport Hasil Ujian, dan ujian online yang aman dengan anti-contek. Cocok untuk sekolah, bimbel, private, dan homeschooling.')
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head-meta')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <!-- Swiper v11 (verified) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11.2.10/swiper-bundle.min.css">
    <script src="https://cdn.jsdelivr.net/npm/swiper@11.2.10/swiper-bundle.min.js"></script>
    <script src="https://unpkg.com/lucide@0.469.0/dist/umd/lucide.min.js"></script>
    @php
        // Gambar edukasi self-hosted (sudah dikurasi & diverifikasi relevan), fallback ilustrasi lokal.
        $imgs = [
            'hero'       => ['assets/media/landing/hero.jpg',       'assets/media/illustrations/sketchy-1/2.png'],
            'fitur'      => ['assets/media/landing/fitur.jpg',      'assets/media/illustrations/sketchy-1/5.png'],
            'sekolah'    => ['assets/media/landing/sekolah.jpg',    'assets/media/illustrations/sketchy-1/8.png'],
            'bimbel'     => ['assets/media/landing/bimbel.jpg',     'assets/media/illustrations/sketchy-1/12.png'],
            'homeschool' => ['assets/media/landing/homeschool.jpg', 'assets/media/illustrations/dozzy-1/2.png'],
            'stats'      => ['assets/media/landing/stats.jpg',      'assets/media/illustrations/dozzy-1/5.png'],
        ];
    @endphp
    <style>
        :root{
            --navy:#0B1F3A; --navy2:#142C52;
            --indigo:#4F46E5; --purple:#7C3AED; --blue:#1D4ED8;
            --gold:#D4A017; --gold-l:#F4C430; --amber:#F59E0B;
            --emerald:#059669; --teal:#0EA5E9; --pink:#EC4899;
            --ink:#16213A; --muted:#64748B; --line:#E7EBF3;
            --grad-primary:linear-gradient(135deg,#4F46E5,#7C3AED);
            --grad-hero:linear-gradient(120deg,#4F46E5 0%,#7C3AED 45%,#1D4ED8 100%);
            --grad-gold:linear-gradient(135deg,#F4C430,#D4A017);
            --grad-navy:linear-gradient(180deg,#0B1F3A,#142C52);
        }
        *{box-sizing:border-box;margin:0;padding:0}
        html,body{height:100%;overflow:hidden}
        body{font-family:'Plus Jakarta Sans',sans-serif;color:var(--ink);background:#fff;-webkit-font-smoothing:antialiased}
        .serif{font-family:'Playfair Display',serif}
        a{text-decoration:none;color:inherit}
        img{max-width:100%}

        /* ---------- NAV ---------- */
        .nav{position:fixed;top:0;left:0;right:0;z-index:60;height:74px;display:flex;align-items:center;justify-content:space-between;
            padding:0 clamp(18px,4vw,56px);background:rgba(255,255,255,.72);backdrop-filter:saturate(160%) blur(14px);border-bottom:1px solid var(--line)}
        .brand{display:flex;align-items:center;gap:10px;font-weight:800;font-size:20px;color:var(--navy)}
        .brand img{height:34px;width:auto;border-radius:8px}
        .nav-links{display:flex;align-items:center;gap:4px}
        .nav-links a{padding:9px 14px;border-radius:10px;font-weight:600;font-size:14.5px;color:#334155;transition:.18s;cursor:pointer}
        .nav-links a:hover{background:rgba(79,70,229,.08);color:var(--indigo)}
        .nav-cta{display:flex;align-items:center;gap:10px}
        .btn{display:inline-flex;align-items:center;gap:8px;border:none;cursor:pointer;font-weight:700;border-radius:12px;padding:11px 20px;font-size:14.5px;transition:.2s;white-space:nowrap}
        .btn-ghost{background:transparent;color:var(--navy)} .btn-ghost:hover{background:rgba(11,31,58,.06)}
        .btn-primary{background:var(--grad-primary);color:#fff;box-shadow:0 10px 22px rgba(79,70,229,.32)}
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 16px 30px rgba(79,70,229,.4)}
        .btn-gold{background:var(--grad-gold);color:#241a00}
        .btn-light{background:#fff;color:var(--navy);box-shadow:0 8px 20px rgba(0,0,0,.08)}
        .nav-toggle{display:none;background:none;border:none;cursor:pointer;color:var(--navy)}

        /* ---------- SWIPER FULLSCREEN ---------- */
        .swiper{width:100vw;height:100vh}
        .swiper-slide{width:100vw;height:100vh;overflow:hidden}
        .panel{width:100%;height:100%;padding:92px clamp(20px,6vw,104px) 60px;display:flex;flex-direction:column;justify-content:safe center;position:relative;overflow-x:hidden;overflow-y:auto}
        .panel-mesh{background:radial-gradient(900px 480px at 92% -10%,rgba(124,58,237,.10),transparent 60%),radial-gradient(820px 460px at -8% 110%,rgba(79,70,229,.10),transparent 55%),#fff}
        .panel-cream{background:#FAF7F0}
        .panel-navy{background:var(--grad-navy);color:#fff}
        .eyebrow{display:inline-flex;align-items:center;gap:8px;align-self:flex-start;padding:7px 16px;border-radius:999px;font-weight:700;font-size:13px;background:rgba(79,70,229,.10);color:var(--indigo);margin-bottom:20px}
        .panel-navy .eyebrow{background:rgba(255,255,255,.12);color:var(--gold-l)}
        .h-title{font-size:clamp(28px,4.4vw,58px);line-height:1.06;font-weight:800;letter-spacing:-.02em;color:var(--navy)}
        .panel-navy .h-title{color:#fff}
        .grad-text{background:var(--grad-primary);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
        .lead{font-size:clamp(14.5px,1.3vw,17.5px);color:var(--muted);max-width:540px;margin-top:16px;line-height:1.6}
        .panel-navy .lead{color:rgba(255,255,255,.78)}
        .section-head{max-width:720px;margin-bottom:30px}

        /* ---------- HERO ---------- */
        .hero-wrap{display:grid;grid-template-columns:1.05fr 1fr;gap:46px;align-items:center;width:100%;max-width:1280px;margin:0 auto}
        .hero-cta{display:flex;gap:14px;margin-top:26px;flex-wrap:wrap}
        .hero-art{position:relative}
        .hero-img-frame{border-radius:24px;overflow:hidden;box-shadow:0 30px 70px rgba(16,24,64,.22);background:var(--grad-hero);padding:12px}
        .hero-img-frame img{border-radius:14px;display:block;width:100%;height:auto;aspect-ratio:4/3;object-fit:cover}
        .card-float{position:absolute;background:#fff;border-radius:16px;box-shadow:0 24px 60px rgba(16,24,64,.18);padding:12px 16px;display:flex;align-items:center;gap:11px;font-weight:700;font-size:13px}
        .card-float.f1{top:16px;left:8px} .card-float.f2{bottom:16px;right:8px}
        .chip-ic{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;color:#fff;flex:0 0 40px}
        .trust{display:flex;align-items:center;gap:24px;margin-top:28px;flex-wrap:wrap;opacity:.9}
        .trust span{font-weight:700;color:var(--muted);font-size:13px}

        /* ---------- FITUR (2 kolom + gambar) ---------- */
        .fitur-wrap{display:grid;grid-template-columns:.9fr 1.1fr;gap:46px;align-items:center;width:100%;max-width:1280px;margin:0 auto}
        .fitur-photo{border-radius:22px;overflow:hidden;position:relative;box-shadow:0 24px 60px rgba(16,24,64,.16)}
        .fitur-photo img{width:100%;height:420px;object-fit:cover;display:block}
        .fitur-photo .ovl{position:absolute;inset:auto 0 0 0;padding:22px;background:linear-gradient(transparent,rgba(11,31,58,.82));color:#fff}
        .grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .feature{background:#fff;border:1px solid var(--line);border-radius:16px;padding:18px;transition:.22s}
        .feature:hover{transform:translateY(-5px);box-shadow:0 18px 40px rgba(16,24,64,.12);border-color:transparent}
        .feature .ic{width:46px;height:46px;border-radius:12px;display:grid;place-items:center;color:#fff;margin-bottom:11px}
        .feature h3{font-size:15.5px;font-weight:700;margin-bottom:5px;color:var(--navy)}
        .feature p{color:var(--muted);font-size:12.8px;line-height:1.5}

        /* ---------- SOLUSI ---------- */
        .grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;width:100%;max-width:1200px;margin:0 auto}
        .sol{background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 14px 40px rgba(16,24,64,.08);transition:.22s;display:flex;flex-direction:column}
        .sol:hover{transform:translateY(-6px);box-shadow:0 26px 60px rgba(16,24,64,.16)}
        .sol .ph{height:188px;overflow:hidden;position:relative}
        .sol .ph img{width:100%;height:100%;object-fit:cover;transition:.45s}
        .sol:hover .ph img{transform:scale(1.07)}
        .sol .ph .tag{position:absolute;top:12px;left:12px;background:rgba(255,255,255,.92);color:var(--navy);font-weight:700;font-size:12px;padding:6px 12px;border-radius:999px}
        .sol .bd{padding:20px}
        .sol .bd h3{font-size:18px;color:var(--navy);margin-bottom:7px}
        .sol .bd p{color:var(--muted);font-size:13.5px;line-height:1.55}
        .sol .bd a{display:inline-flex;align-items:center;gap:6px;margin-top:12px;color:var(--indigo);font-weight:700;font-size:14px}

        /* ---------- HARGA ---------- */
        .price-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;width:100%;max-width:1280px;margin:0 auto}
        .plan{background:#fff;border:1.5px solid var(--line);border-radius:22px;padding:24px 22px;display:flex;flex-direction:column;transition:.22s;position:relative}
        .plan:hover{transform:translateY(-6px);box-shadow:0 24px 56px rgba(16,24,64,.14)}
        .plan.feat{background:var(--grad-navy);color:#fff;border-color:transparent;transform:scale(1.03)}
        .plan.feat:hover{transform:scale(1.03) translateY(-6px)}
        .plan .badge{position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:var(--grad-gold);color:#241a00;font-weight:800;font-size:11.5px;padding:6px 15px;border-radius:999px;white-space:nowrap}
        .plan .pname{font-weight:700;font-size:15.5px;display:flex;align-items:center;gap:9px}
        .plan .pic{width:40px;height:40px;border-radius:11px;display:grid;place-items:center;color:#fff;flex:0 0 40px}
        .plan .price{margin:16px 0 2px;font-size:31px;font-weight:800;letter-spacing:-.02em}
        .plan .per{color:var(--muted);font-size:12.5px;font-weight:600} .plan.feat .per{color:rgba(255,255,255,.65)}
        .plan .desc{font-size:12px;color:var(--muted);margin-top:4px} .plan.feat .desc{color:rgba(255,255,255,.6)}
        .plan ul{list-style:none;margin:16px 0;display:flex;flex-direction:column;gap:9px;flex:1}
        .plan li{display:flex;align-items:flex-start;gap:9px;font-size:13px;color:#475569} .plan.feat li{color:rgba(255,255,255,.85)}
        .plan li svg{flex:0 0 17px;color:var(--emerald);margin-top:1px} .plan.feat li svg{color:var(--gold-l)}

        /* ---------- STATS + SUMBER DAYA ---------- */
        .stats-bg{position:absolute;inset:0;z-index:0}
        .stats-bg img{width:100%;height:100%;object-fit:cover;opacity:.16}
        .stats-bg::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(11,31,58,.7),rgba(20,44,82,.92))}
        .panel-navy .panel-inner{position:relative;z-index:1;width:100%}
        .stats{display:grid;grid-template-columns:repeat(4,1fr);gap:28px;width:100%;max-width:1080px;margin:0 auto 46px;text-align:center}
        .stat .n{font-size:clamp(30px,3.6vw,52px);font-weight:800;color:#fff;line-height:1}
        .stat .l{color:rgba(255,255,255,.7);font-weight:600;margin-top:7px;letter-spacing:.04em;font-size:12.5px;text-transform:uppercase}
        .res-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;width:100%;max-width:1120px;margin:0 auto}
        .res{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:22px;transition:.22s}
        .res:hover{transform:translateY(-5px);background:rgba(255,255,255,.1)}
        .res .ic{width:48px;height:48px;border-radius:13px;display:grid;place-items:center;color:#fff;margin-bottom:12px}
        .res h3{color:#fff;font-size:16px;margin-bottom:5px} .res p{color:rgba(255,255,255,.7);font-size:13px}

        /* ---------- FOOTER ---------- */
        .foot-wrap{width:100%;max-width:1200px;margin:0 auto;display:grid;grid-template-columns:1.4fr 1fr 1fr 1.2fr;gap:40px}
        .foot-col h4{font-size:13.5px;text-transform:uppercase;letter-spacing:.06em;color:var(--gold-l);margin-bottom:14px}
        .foot-col a,.foot-col p{display:block;color:rgba(255,255,255,.72);font-size:13.8px;margin-bottom:9px;transition:.18s;cursor:pointer}
        .foot-col a:hover{color:#fff}
        .foot-bottom{border-top:1px solid rgba(255,255,255,.12);margin-top:30px;padding-top:18px;display:flex;justify-content:space-between;color:rgba(255,255,255,.6);font-size:13px;flex-wrap:wrap;gap:10px}

        /* ---------- SWIPER NAV (semi-transparan) ---------- */
        .swiper-button-prev,.swiper-button-next{width:54px;height:54px;border-radius:50%;background:rgba(255,255,255,.5);
            backdrop-filter:blur(8px);border:1.5px solid rgba(11,31,58,.14);color:var(--navy);
            box-shadow:0 10px 28px rgba(16,24,64,.12);transition:.2s;z-index:40}
        .swiper-button-prev:hover,.swiper-button-next:hover{background:rgba(255,255,255,.82);color:var(--indigo);border-color:var(--indigo)}
        .swiper-button-prev::after,.swiper-button-next::after{content:''}
        .swiper-button-prev{left:18px}.swiper-button-next{right:18px}
        .swiper-button-disabled{opacity:.25}
        /* pagination */
        .swiper-pagination-bullet{width:9px;height:9px;background:rgba(11,31,58,.25);opacity:1;transition:.25s}
        .swiper-pagination-bullet-active{width:30px;border-radius:999px;background:var(--grad-primary)}
        .swiper-pagination.is-dark .swiper-pagination-bullet{background:rgba(255,255,255,.45)}
        .swiper-pagination.is-dark .swiper-pagination-bullet-active{background:var(--gold-l)}

        .hint{position:fixed;bottom:22px;right:24px;z-index:40;display:flex;align-items:center;gap:8px;color:var(--muted);
            font-size:12.5px;font-weight:600;background:rgba(255,255,255,.7);padding:8px 14px;border-radius:999px;backdrop-filter:blur(6px)}

        /* ---------- LAYAR MENENGAH (tablet landscape / laptop kecil): grid jangan terlalu rapat ---------- */
        @media (min-width:1025px) and (max-width:1280px){
            .panel{padding-left:clamp(20px,4vw,56px);padding-right:clamp(20px,4vw,56px)}
            .price-grid{grid-template-columns:repeat(2,1fr)}
            .res-grid{grid-template-columns:repeat(2,1fr)}
        }

        /* ---------- LAYAR PENDEK: rapatkan agar konten tidak terpotong ---------- */
        @media (min-width:1025px) and (max-height:780px){
            .panel{padding-top:82px;padding-bottom:26px}
            .h-title{font-size:clamp(22px,3vw,38px)}
            .section-head{margin-bottom:18px}
            .plan{padding:16px 16px;border-radius:18px}
            .plan .price{font-size:26px;margin:10px 0 2px}
            .plan ul{gap:6px;margin:12px 0}
            .plan li{font-size:12px}
            .sol .ph{height:140px}
            .fitur-photo img{height:330px}
            .grid2{gap:12px}
            .feature{padding:14px}
            .stats{margin-bottom:26px}
            .res{padding:16px}
        }

        /* ---------- MOBILE/TABLET ≤1024 (incl. iPad portrait): vertical fallback, Swiper tidak di-init ---------- */
        @media (max-width:1024px){
            html,body{overflow:auto}
            .swiper{height:auto;overflow:visible}
            .swiper-wrapper{display:block;transform:none !important;height:auto}
            .swiper-slide{width:100%;height:auto}
            .panel{height:auto;min-height:auto;padding:104px 22px 56px}
            .hero-wrap,.fitur-wrap{grid-template-columns:1fr;gap:34px}
            .grid2,.grid3,.price-grid,.res-grid,.stats,.foot-wrap{grid-template-columns:1fr}
            .stats{gap:30px}
            .swiper-button-prev,.swiper-button-next,.swiper-pagination,.hint{display:none}
            .nav-links{display:none}.nav-toggle{display:block}
            .nav-cta .btn{display:none}   /* tombol Login/Portal pindah ke drawer hamburger agar nav tidak melimpah di HP */
            .nav{padding:0 18px}
            .plan.feat{transform:none}
            .card-float{position:static;margin-top:12px;display:inline-flex}
        }
        #mdrawer{position:fixed;inset:0;z-index:80;background:rgba(11,31,58,.97);display:none;flex-direction:column;padding:28px 24px;gap:6px}
        #mdrawer.open{display:flex}
        #mdrawer a{color:#fff;padding:14px 8px;border-bottom:1px solid rgba(255,255,255,.08);font-weight:600;font-size:17px;cursor:pointer}
        #mdrawer .close{align-self:flex-end;background:none;border:none;color:#fff;cursor:pointer;margin-bottom:10px}
    </style>
</head>
<body>

    <!-- ============ NAV ============ -->
    <nav class="nav">
        <a class="brand" data-scroll="beranda">
            <img src="{{ asset('assets/media/logos/lms.png') }}" alt="LMS Sync">
            <span>LMS&nbsp;Sync</span>
        </a>
        <div class="nav-links">
            <a data-scroll="beranda">Beranda</a>
            <a data-scroll="fitur">Fitur</a>
            <a data-scroll="solusi">Solusi</a>
            <a data-scroll="harga">Harga</a>
            <a data-scroll="sumber-daya">Sumber Daya</a>
            <a data-scroll="kontak">Kontak</a>
        </div>
        <div class="nav-cta">
            <a href="{{ route('login') }}" class="btn btn-ghost">Login Staf</a>
            <a href="{{ route('student.login') }}" class="btn btn-primary">Portal Siswa <i data-lucide="arrow-right" style="width:17px"></i></a>
            <button class="nav-toggle" onclick="document.getElementById('mdrawer').classList.add('open')"><i data-lucide="menu" style="width:28px"></i></button>
        </div>
    </nav>

    <div id="mdrawer">
        <button class="close" onclick="document.getElementById('mdrawer').classList.remove('open')"><i data-lucide="x" style="width:30px"></i></button>
        <a data-scroll="beranda">Beranda</a>
        <a data-scroll="fitur">Fitur</a>
        <a data-scroll="solusi">Solusi</a>
        <a data-scroll="harga">Harga</a>
        <a data-scroll="sumber-daya">Sumber Daya</a>
        <a data-scroll="kontak">Kontak</a>
        <a href="{{ route('login') }}">🔐 Login Staf / Guru</a>
        <a href="{{ route('student.login') }}">🎓 Portal Siswa</a>
    </div>

    <!-- ============ SWIPER ============ -->
    <div class="swiper">
        <div class="swiper-wrapper">

            <!-- ===== SLIDE 1: HERO ===== -->
            <div class="swiper-slide">
                <section class="panel panel-mesh" id="beranda">
                    <div class="hero-wrap">
                        <div>
                            <span class="eyebrow"><i data-lucide="sparkles" style="width:15px"></i> Belajar Cerdas, Masa Depan Cerah</span>
                            <h1 class="h-title">Platform <span class="grad-text">LMS &amp; CBT</span><br>untuk Sekolah Elite &amp; Ternama</h1>
                            <p class="lead">Satu ekosistem digital untuk kelola pembelajaran, ujian online (CBT), absensi, Raport Hasil Ujian, hingga analitik prestasi siswa — elegan, cepat, dan terpercaya.</p>
                            <div class="hero-cta">
                                <a href="{{ route('student.login') }}" class="btn btn-primary">Mulai Sekarang <i data-lucide="arrow-right" style="width:18px"></i></a>
                                <a data-scroll="harga" class="btn btn-light"><i data-lucide="play" style="width:16px"></i> Lihat Paket Harga</a>
                            </div>
                            <div class="trust"><span>⭐ 98% kepuasan</span><span>🏫 1.200+ institusi</span><span>🔒 Data aman &amp; terenkripsi</span></div>
                        </div>
                        <div class="hero-art">
                            <div class="hero-img-frame">
                                <img src="{{ asset($imgs['hero'][0]) }}" alt="Siswa belajar online" onerror="this.onerror=null;this.src='{{ asset($imgs['hero'][1]) }}'">
                            </div>
                            <div class="card-float f1"><span class="chip-ic" style="background:var(--grad-primary)"><i data-lucide="graduation-cap" style="width:20px"></i></span><div>CBT Online<br><span style="color:var(--muted);font-weight:600">Ujian anti-curang</span></div></div>
                            <div class="card-float f2"><span class="chip-ic" style="background:linear-gradient(135deg,#10B981,#059669)"><i data-lucide="trending-up" style="width:20px"></i></span><div>+38% Prestasi<br><span style="color:var(--muted);font-weight:600">Analitik real-time</span></div></div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- ===== SLIDE 2: FITUR ===== -->
            <div class="swiper-slide">
                <section class="panel" id="fitur">
                    <div class="fitur-wrap">
                        <div>
                            <div class="fitur-photo">
                                <img src="{{ asset($imgs['fitur'][0]) }}" alt="Kolaborasi belajar" onerror="this.onerror=null;this.src='{{ asset($imgs['fitur'][1]) }}'">
                                <div class="ovl"><strong style="font-size:18px">Belajar Tanpa Batas</strong><div style="opacity:.85;font-size:13px;margin-top:4px">Guru, siswa &amp; admin dalam satu platform</div></div>
                            </div>
                        </div>
                        <div>
                            <span class="eyebrow"><i data-lucide="layout-grid" style="width:15px"></i> Fitur Unggulan</span>
                            <h2 class="h-title" style="font-size:clamp(24px,3vw,38px);margin-bottom:18px">Semua kebutuhan sekolah modern</h2>
                            <div class="grid2">
                                @php
                                $features=[
                                    ['book-open','Modul Pembelajaran','indigo','Materi PDF, dokumen & video kapan saja.'],
                                    ['file-check-2','Ujian Online (CBT)','emerald','Bank soal, timer & nilai otomatis anti-curang.'],
                                    ['fingerprint','Absensi Digital','amber','Absen harian & per mapel, notifikasi ortu.'],
                                    ['award','Raport Hasil Ujian & Peringkat','blue','Rapor digital + ranking kelas otomatis.'],
                                    ['trophy','Gamifikasi','pink','Lencana, poin & leaderboard motivasi belajar.'],
                                    ['bar-chart-3','Analitik Prestasi','teal','Pantau performa lewat dashboard interaktif.'],
                                ];
                                $bg=['indigo'=>'var(--grad-primary)','emerald'=>'linear-gradient(135deg,#10B981,#059669)','amber'=>'var(--grad-gold)','blue'=>'linear-gradient(135deg,#3B82F6,#1D4ED8)','pink'=>'linear-gradient(135deg,#F472B6,#EC4899)','teal'=>'linear-gradient(135deg,#38BDF8,#0EA5E9)'];
                                @endphp
                                @foreach($features as $f)
                                <div class="feature"><div class="ic" style="background:{{ $bg[$f[2]] }}"><i data-lucide="{{ $f[0] }}" style="width:22px"></i></div><h3>{{ $f[1] }}</h3><p>{{ $f[3] }}</p></div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- ===== SLIDE 3: SOLUSI ===== -->
            <div class="swiper-slide">
                <section class="panel panel-cream" id="solusi">
                    <div class="section-head">
                        <span class="eyebrow"><i data-lucide="layers" style="width:15px"></i> Solusi</span>
                        <h2 class="h-title" style="font-size:clamp(26px,3.4vw,44px)">Cocok untuk setiap institusi pendidikan</h2>
                        <p class="lead">Dari sekolah, lembaga bimbel, hingga home schooling.</p>
                    </div>
                    <div class="grid3">
                        @php
                        $sol=[
                            ['sekolah','Sekolah (SD/SMP/SMA)','Digitalisasi penuh KBM, ujian, & administrasi sekolah dalam satu sistem terpadu.'],
                            ['bimbel','Bimbel & Lembaga Les','Kelola kelas, jadwal tentor, bank soal try-out, & laporan progres siswa.'],
                            ['homeschool','Home Schooling','Belajar mandiri di rumah dengan modul terstruktur, ujian online, & laporan untuk orang tua.'],
                        ];
                        @endphp
                        @foreach($sol as $s)
                        <div class="sol">
                            <div class="ph">
                                <img src="{{ asset($imgs[$s[0]][0]) }}" alt="{{ $s[1] }}" onerror="this.onerror=null;this.src='{{ asset($imgs[$s[0]][1]) }}'">
                                <span class="tag">{{ $s[1] }}</span>
                            </div>
                            <div class="bd"><h3>{{ $s[1] }}</h3><p>{{ $s[2] }}</p><a data-scroll="harga">Lihat paket <i data-lucide="arrow-right" style="width:16px"></i></a></div>
                        </div>
                        @endforeach
                    </div>
                </section>
            </div>

            <!-- ===== SLIDE 4: HARGA ===== -->
            <div class="swiper-slide">
                <section class="panel panel-mesh" id="harga">
                    <div class="section-head" style="margin:0 auto 26px;text-align:center;max-width:760px">
                        <span class="eyebrow" style="align-self:center"><i data-lucide="wallet" style="width:15px"></i> Harga Berlangganan</span>
                        <h2 class="h-title" style="font-size:clamp(26px,3.4vw,44px)">Investasi terbaik untuk pendidikan</h2>
                        <p class="lead" style="margin:10px auto 0">Pilih paket sesuai kebutuhan — bulanan atau lifetime.</p>
                    </div>
                    <div class="price-grid">
                        <!-- Home Schooling (paling murah) -->
                        <div class="plan">
                            <div class="pname"><span class="pic" style="background:linear-gradient(135deg,#38BDF8,#0EA5E9)"><i data-lucide="home" style="width:19px"></i></span> Home Schooling</div>
                            <div class="price">Rp 199<span style="font-size:15px">rb</span></div>
                            <div class="per">/ bulan</div>
                            <div class="desc">Untuk keluarga & belajar mandiri di rumah.</div>
                            <ul>
                                <li><i data-lucide="check" style="width:17px"></i> Hingga 25 anak / siswa</li>
                                <li><i data-lucide="check" style="width:17px"></i> Modul & tugas terstruktur</li>
                                <li><i data-lucide="check" style="width:17px"></i> Ujian online (CBT)</li>
                                <li><i data-lucide="check" style="width:17px"></i> Raport Hasil Ujian & laporan ortu</li>
                                <li><i data-lucide="check" style="width:17px"></i> Dukungan email</li>
                            </ul>
                            <a href="{{ route('login') }}" class="btn btn-light" style="justify-content:center">Pilih Home</a>
                        </div>
                        <!-- Bimbel -->
                        <div class="plan">
                            <div class="pname"><span class="pic" style="background:linear-gradient(135deg,#F472B6,#EC4899)"><i data-lucide="users" style="width:19px"></i></span> Bimbel / Les</div>
                            <div class="price">Rp 350<span style="font-size:15px">rb</span></div>
                            <div class="per">/ bulan</div>
                            <div class="desc">Untuk lembaga bimbingan belajar & les.</div>
                            <ul>
                                <li><i data-lucide="check" style="width:17px"></i> Hingga 300 siswa</li>
                                <li><i data-lucide="check" style="width:17px"></i> Kelas, jadwal & tentor</li>
                                <li><i data-lucide="check" style="width:17px"></i> Bank soal & try-out CBT</li>
                                <li><i data-lucide="check" style="width:17px"></i> Leaderboard & lencana</li>
                                <li><i data-lucide="check" style="width:17px"></i> Dukungan email</li>
                            </ul>
                            <a href="{{ route('login') }}" class="btn btn-light" style="justify-content:center">Pilih Bimbel</a>
                        </div>
                        <!-- Sekolah (POPULER) -->
                        <div class="plan feat">
                            <span class="badge">⭐ PALING POPULER</span>
                            <div class="pname"><span class="pic" style="background:var(--grad-gold);color:#241a00"><i data-lucide="school" style="width:19px"></i></span> Sekolah</div>
                            <div class="price">Rp 750<span style="font-size:15px">rb</span></div>
                            <div class="per">/ bulan</div>
                            <div class="desc">Untuk SD / SMP / SMA negeri & swasta.</div>
                            <ul>
                                <li><i data-lucide="check" style="width:17px"></i> Hingga 1.000 siswa</li>
                                <li><i data-lucide="check" style="width:17px"></i> Modul & tugas unlimited</li>
                                <li><i data-lucide="check" style="width:17px"></i> CBT, absensi & Raport Hasil Ujian</li>
                                <li><i data-lucide="check" style="width:17px"></i> Notifikasi orang tua</li>
                                <li><i data-lucide="check" style="width:17px"></i> Dukungan prioritas</li>
                            </ul>
                            <a href="{{ route('login') }}" class="btn btn-gold" style="justify-content:center">Pilih Sekolah</a>
                        </div>
                        <!-- Lifetime -->
                        <div class="plan">
                            <div class="pname"><span class="pic" style="background:linear-gradient(135deg,#10B981,#059669)"><i data-lucide="infinity" style="width:19px"></i></span> Lifetime Sekolah</div>
                            <div class="price">Rp 25<span style="font-size:15px">jt</span></div>
                            <div class="per">sekali bayar</div>
                            <div class="desc">Lisensi seumur hidup untuk satu sekolah.</div>
                            <ul>
                                <li><i data-lucide="check" style="width:17px"></i> Semua fitur paket Sekolah</li>
                                <li><i data-lucide="check" style="width:17px"></i> Tanpa biaya bulanan</li>
                                <li><i data-lucide="check" style="width:17px"></i> Update gratis selamanya</li>
                                <li><i data-lucide="check" style="width:17px"></i> Opsi server on-premise</li>
                                <li><i data-lucide="check" style="width:17px"></i> Pendampingan implementasi</li>
                            </ul>
                            <a href="{{ route('login') }}" class="btn btn-light" style="justify-content:center">Ambil Lifetime</a>
                        </div>
                    </div>
                </section>
            </div>

            <!-- ===== SLIDE 5: STATISTIK + SUMBER DAYA ===== -->
            <div class="swiper-slide">
                <section class="panel panel-navy" id="sumber-daya">
                    <div class="stats-bg"><img src="{{ asset($imgs['stats'][0]) }}" alt="" onerror="this.onerror=null;this.style.display='none'"></div>
                    <div class="panel-inner">
                        <div class="stats">
                            <div class="stat"><div class="n">1.200+</div><div class="l">Institusi</div></div>
                            <div class="stat"><div class="n">45.000+</div><div class="l">Materi & Soal</div></div>
                            <div class="stat"><div class="n">2,5jt+</div><div class="l">Siswa Aktif</div></div>
                            <div class="stat"><div class="n">98%</div><div class="l">Kepuasan</div></div>
                        </div>
                        <div class="section-head" style="max-width:680px">
                            <span class="eyebrow"><i data-lucide="book-marked" style="width:15px"></i> Sumber Daya</span>
                            <h2 class="h-title" style="font-size:clamp(24px,3vw,38px);color:#fff">Belajar memaksimalkan platform</h2>
                        </div>
                        <div class="res-grid">
                            @php
                            $res=[['newspaper','Blog & Artikel','indigo','Tips pengajaran & studi kasus sekolah.'],['graduation-cap','Panduan & Tutorial','emerald','Dokumentasi lengkap guru & admin.'],['video','Webinar & Pelatihan','amber','Sesi pelatihan rutin tim ahli.'],['life-buoy','Pusat Bantuan','pink','Tim support siap membantu kapan saja.']];
                            @endphp
                            @foreach($res as $r)
                            <div class="res"><div class="ic" style="background:{{ $bg[$r[2]] }}"><i data-lucide="{{ $r[0] }}" style="width:22px"></i></div><h3>{{ $r[1] }}</h3><p>{{ $r[3] }}</p></div>
                            @endforeach
                        </div>
                    </div>
                </section>
            </div>

            <!-- ===== SLIDE 6: CTA + FOOTER ===== -->
            <div class="swiper-slide">
                <section class="panel panel-navy" id="kontak" style="justify-content:space-between">
                    <div style="width:100%;max-width:1100px;margin:0 auto;text-align:center;padding-top:6px">
                        <h2 class="h-title serif" style="color:#fff;font-size:clamp(28px,4.2vw,54px)">Siap menjadikan sekolah Anda<br><span style="color:var(--gold-l)">lebih unggul &amp; modern?</span></h2>
                        <p class="lead" style="color:rgba(255,255,255,.8);margin:16px auto 0">Mulai gratis hari ini, atau jadwalkan demo bersama tim kami.</p>
                        <div style="display:flex;gap:14px;justify-content:center;margin-top:24px;flex-wrap:wrap">
                            <a href="{{ route('student.login') }}" class="btn btn-gold">Coba Gratis Sekarang <i data-lucide="arrow-right" style="width:18px"></i></a>
                            <a data-scroll="harga" class="btn btn-light">Lihat Harga</a>
                        </div>
                    </div>
                    <div style="width:100%">
                        <div class="foot-wrap">
                            <div class="foot-col">
                                <div class="brand" style="color:#fff;margin-bottom:12px"><img src="{{ asset('assets/media/logos/lms.png') }}" style="height:34px;border-radius:8px" alt=""> <span>LMS&nbsp;Sync</span></div>
                                <p style="max-width:280px">Platform LMS &amp; CBT untuk sekolah, bimbel, dan home schooling modern di Indonesia.</p>
                                <p style="margin-top:6px">📧 halo@lmssync.id &nbsp;•&nbsp; 📞 0800-1234-5678</p>
                            </div>
                            <div class="foot-col"><h4>Produk</h4><a data-scroll="fitur">Fitur</a><a data-scroll="solusi">Solusi</a><a data-scroll="harga">Harga</a><a href="{{ route('student.login') }}">Portal Siswa</a></div>
                            <div class="foot-col"><h4>Perusahaan</h4><a data-scroll="kontak">Tentang Kami</a><a data-scroll="sumber-daya">Sumber Daya</a><a data-scroll="kontak">Kontak</a><a href="{{ route('login') }}">Login Staf</a></div>
                            <div class="foot-col"><h4>Mulai</h4><p>Daftarkan sekolah Anda dan rasakan transformasi digital pendidikan.</p><a href="{{ route('student.login') }}" class="btn btn-gold" style="margin-top:4px">Mulai Sekarang</a></div>
                        </div>
                        <div class="foot-bottom">
                            <span>© {{ date('Y') }} LMS Sync. Hak Cipta Dilindungi.</span>
                            <span style="display:flex;gap:22px"><span style="color:rgba(255,255,255,.55)">Kebijakan Privasi</span><span style="color:rgba(255,255,255,.55)">Syarat &amp; Ketentuan</span></span>
                        </div>
                    </div>
                </section>
            </div>

        </div>

        <!-- nav + pagination -->
        <div class="swiper-button-prev"><i data-lucide="chevron-left" style="width:24px"></i></div>
        <div class="swiper-button-next"><i data-lucide="chevron-right" style="width:24px"></i></div>
        <div class="swiper-pagination"></div>
    </div>

    <div class="hint"><i data-lucide="mouse" style="width:15px"></i> Putar scroll mouse untuk menjelajah →</div>

    <script>
        window.addEventListener('error', function(e){ try { console.warn('Landing JS suppressed:', e.message); } catch(_){} });
        function drawIcons(){ try { if (window.lucide && lucide.createIcons) lucide.createIcons(); } catch(e){} }
        drawIcons();

        /* ---- Swiper: vertical wheel -> horizontal slide, NO mouse drag (config terverifikasi) ---- */
        let swiper = null;
        const idIndex = { 'beranda':0, 'fitur':1, 'solusi':2, 'harga':3, 'sumber-daya':4, 'kontak':5 };

        // Bullet pagination kontras saat slide berlatar navy (gelap).
        function setPagTheme(s){
            const navy = s.slides[s.activeIndex] && s.slides[s.activeIndex].querySelector('.panel-navy');
            const el = s.pagination && s.pagination.el;
            if (el) el.classList.toggle('is-dark', !!navy);
        }

        function initSwiper(){
            if (swiper) return;
            if (!window.Swiper){ document.querySelectorAll('.swiper-slide').forEach(function(s){ s.style.display='block'; }); document.documentElement.style.overflow='auto'; document.body.style.overflow='auto'; return; }
            swiper = new Swiper('.swiper', {
                direction: 'horizontal',
                slidesPerView: 1,
                speed: 650,
                mousewheel: { enabled: true, forceToAxis: false, releaseOnEdges: true },
                simulateTouch: false,          // tanpa drag mouse
                allowTouchMove: true,          // tetap bisa swipe di layar sentuh
                keyboard: { enabled: true, onlyInViewport: true },
                navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
                pagination: { el: '.swiper-pagination', clickable: true },
                on: { init(){ setPagTheme(this); }, slideChange(){ setPagTheme(this); } },
            });
            drawIcons();
        }
        function destroySwiper(){ if (swiper){ swiper.destroy(true, true); swiper = null; } }

        const mq = window.matchMedia('(min-width: 1025px)');
        function handleMq(e){ e.matches ? initSwiper() : destroySwiper(); }
        handleMq(mq);
        if (mq.addEventListener) mq.addEventListener('change', handleMq); else if (mq.addListener) mq.addListener(handleMq);

        /* ---- Nav / anchor: slideTo di desktop, scroll di mobile ---- */
        function go(id){
            const idx = idIndex[id];
            if (swiper && mq.matches && idx !== undefined){ swiper.slideTo(idx); }
            else { var _t = document.getElementById(id); if (_t) _t.scrollIntoView({ behavior:'smooth', block:'start' }); }
            closeDrawer();
        }
        document.querySelectorAll('[data-scroll]').forEach(el=>{
            el.addEventListener('click', (e)=>{ e.preventDefault(); go(el.dataset.scroll); });
        });
        function closeDrawer(){ var _d = document.getElementById('mdrawer'); if (_d) _d.classList.remove('open'); }
    </script>
@include('partials.dev-credit')
</body>
</html>
