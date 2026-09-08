{{-- Kop halaman: judul + breadcrumb yang bisa diklik sebagai pintasan menu.

     Dipakai supaya seluruh halaman Data Master punya kop yang seragam — sebelum
     ini sebagian halaman (Data Siswa, Guru, Sekolah, Kelas, Mapel, Tahun Ajaran,
     Gelombang, Penugasan, Enrollment) tidak punya judul maupun breadcrumb sama
     sekali, jadi pengguna tidak tahu sedang di mana.

     Cara pakai:
       @include('partials.kop-halaman', [
           'judul' => 'Data Siswa',
           'jejak' => [
               ['label' => 'Data Master'],                              // teks saja
               ['label' => 'Data Siswa', 'route' => 'students.index'],  // bisa diklik
           ],
       ])

     Ruas terakhir sengaja TIDAK dibuat tautan walau punya route: menautkan ke
     halaman yang sedang dibuka hanya membuat orang menekan tautan yang tidak
     mengubah apa pun. Ruas dengan 'route' yang tidak dikenali diturunkan jadi
     teks biasa, bukan melempar galat — daftar menu bisa berbeda antar peran. --}}
@php
    $jejak = $jejak ?? [];
    $akhir = count($jejak) - 1;
@endphp
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div class="app-container container-fluid px-4 px-lg-6 d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 my-0">{{ $judul }}</h1>
            @isset($catatan)<span class="text-muted fs-7 pt-1">{{ $catatan }}</span>@endisset
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">
                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">
                        <i class="ki-outline ki-home fs-7 text-muted"></i>
                    </a>
                </li>
                @foreach($jejak as $i => $ruas)
                    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                    @php
                        $tautan = ($i !== $akhir && !empty($ruas['route']) && \Illuminate\Support\Facades\Route::has($ruas['route']))
                            ? route($ruas['route'])
                            : null;
                    @endphp
                    <li class="breadcrumb-item {{ $i === $akhir ? 'text-gray-900' : 'text-muted' }}">
                        @if($tautan)
                            <a href="{{ $tautan }}" class="text-muted text-hover-primary">{{ $ruas['label'] }}</a>
                        @else
                            {{ $ruas['label'] }}
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
        @isset($aksi)
            <div class="d-flex align-items-center gap-2">{!! $aksi !!}</div>
        @endisset
    </div>
</div>
