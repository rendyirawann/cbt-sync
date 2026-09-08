@extends('frontend.layout.app')
@section('title', 'Portal Siswa - Beranda')

@section('lebar', 'container-fluid px-3 px-lg-5')

@section('content')
{{-- Beranda ini hanya memuat hal yang berhubungan dengan CBT.

     Sebelumnya halaman ini bawaan LMS: kelas virtual, status absensi, modul
     pembelajaran, tugas menunggu, pengumuman yang dirakit dari modul & tugas,
     dan kartu Hubungi Guru. Semuanya dibuang bersama query-nya di
     PortalController::dashboard(). --}}
<style>
    .student-welcome-card {
        background: linear-gradient(112.14deg, #2575fc 0%, #6a11cb 100%);
        border-radius: 20px;
        position: relative;
        overflow: hidden;
    }
    .student-welcome-img {
        position: absolute;
        right: 20px;
        bottom: -10px;
        height: 180px;
        opacity: 0.9;
    }
</style>

<!--begin::Toolbar-->
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-fluid px-3 px-lg-5 d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Beranda Siswa</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">Portal</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">Beranda</li>
            </ul>
        </div>
        <div class="d-flex align-items-center gap-2 gap-lg-3">
            {{-- Tahun ajaran mengikuti ROMBEL siswa ini, bukan teks yang diketik
                 di view seperti sebelumnya. Kalau siswa belum terdaftar di rombel
                 mana pun, badge-nya sengaja tidak ditampilkan daripada menampilkan
                 tahun yang salah. --}}
            @if($tahunAjaran ?? null)
                <span class="badge badge-light-primary fw-bold px-4 py-3">TA: {{ $tahunAjaran->name }}</span>
            @endif
        </div>
    </div>
</div>

{{-- Ringkasan CBT didahulukan; sambutan menyusul di bawahnya. --}}
<div class="app-content flex-column-fluid pt-0">
    <div class="app-container container-fluid px-3 px-lg-5">
        @include('frontend.dashboard._cbt')
    </div>
</div>

<div class="app-content flex-column-fluid pt-0">
    <div class="app-container container-fluid px-3 px-lg-5">
        @php
            // Kalimat sambutan mengikuti keadaan CBT yang benar-benar ada, bukan
            // jumlah tugas seperti versi LMS-nya.
            $siap = ($cbt['siap_dikerjakan'] ?? collect())->count();
            $jalan = ($cbt['sedang_dikerjakan'] ?? collect())->count();
            $nanti = ($cbt['akan_datang'] ?? collect())->count();
            if ($jalan) {
                $sapaan = 'Ada <strong>' . $jalan . ' ujian</strong> yang belum kamu selesaikan. Lanjutkan sebelum waktunya habis.';
            } elseif ($siap) {
                $sapaan = 'Ada <strong>' . $siap . ' ujian</strong> yang siap kamu kerjakan sekarang.';
            } elseif ($nanti) {
                $sapaan = 'Belum ada ujian yang bisa dikerjakan sekarang. <strong>' . $nanti . ' ujian</strong> sudah terjadwal.';
            } else {
                $sapaan = 'Belum ada ujian yang dijadwalkan untukmu. Nilai ujian yang sudah keluar bisa kamu lihat di atas.';
            }
        @endphp
        <div class="card student-welcome-card mb-10 border-0">
            <div class="card-body p-10 p-lg-15">
                <div class="d-flex flex-column">
                    <h1 class="text-white fw-bolder fs-2qx mb-3">Selamat Datang, {{ auth()->user()->name }}! &#128075;</h1>
                    <p class="text-white opacity-75 fs-5 mb-8 fw-semibold" style="max-width: 600px;">{!! $sapaan !!}</p>
                    <div class="d-flex gap-3">
                        <a href="{{ route('student.exams.index') }}" class="btn btn-white fw-bold px-6">Ujian Saya</a>
                    </div>
                </div>
                <img src="{{ URL::to('assets/media/illustrations/doofenshmirtz/2.png') }}" class="student-welcome-img d-none d-md-block" alt="">
            </div>
        </div>
    </div>
</div>
@endsection
