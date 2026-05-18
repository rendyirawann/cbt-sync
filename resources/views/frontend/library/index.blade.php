@extends('backend.layout.app')
@section('title', 'Perpustakaan Saya')

@section('content')
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Perpustakaan Sekolah</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">Portal</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">Perpustakaan</li>
            </ul>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-xxl">

        {{-- ======== ALERTS ======== --}}
        @if(session('success'))
            <div class="alert alert-success d-flex align-items-center p-5 mb-10">
                <i class="ki-outline ki-shield-tick fs-2hx text-success me-4"></i>
                <div class="d-flex flex-column">
                    <h4 class="mb-1 text-dark fw-bold">Berhasil!</h4>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        {{-- ======== TAB NAVIGATION ======== --}}
        <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold mb-8">
            <li class="nav-item">
                <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#kt_student_library_catalog">
                    Katalog Buku ({{ $books->count() }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_student_borrowings">
                    Buku yang Saya Pinjam ({{ $myBorrowings->count() }})
                </a>
            </li>
        </ul>

        {{-- ======== TAB CONTENT ======== --}}
        <div class="tab-content">
            {{-- TAB 1: CATALOG --}}
            <div class="tab-pane fade show active" id="kt_student_library_catalog" role="tabpanel">
                <div class="row g-6 g-xl-9">
                    @forelse($books as $book)
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="card h-100 card-custom border-0 shadow-sm overflow-hidden position-relative">
                                @if($book->available_stock == 0)
                                    <span class="position-absolute top-0 end-0 badge badge-light-danger m-4 z-index-2">HABIS DIPINJAM</span>
                                @else
                                    <span class="position-absolute top-0 end-0 badge badge-light-success m-4 z-index-2">TERSEDIA</span>
                                @endif
                                
                                {{-- Book Cover --}}
                                <div class="bg-light-primary d-flex flex-center h-200px w-100 position-relative">
                                    @if($book->cover_image)
                                        <img src="{{ Storage::url($book->cover_image) }}" alt="Book Cover" class="h-100 w-100 object-fit-cover">
                                    @else
                                        <div class="text-center">
                                            <i class="ki-outline ki-book fs-5x text-primary opacity-50"></i>
                                        </div>
                                    @endif
                                </div>

                                {{-- Body --}}
                                <div class="card-body p-6">
                                    <h4 class="text-gray-900 fw-extrabold mb-1 text-truncate">{{ $book->title }}</h4>
                                    <span class="text-gray-500 fs-7 d-block mb-3">Oleh: {{ $book->author }}</span>

                                    <div class="separator separator-dashed my-4"></div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex flex-column">
                                            <span class="text-gray-400 fw-bold fs-9 text-uppercase">Penerbit</span>
                                            <span class="fw-bold text-gray-800 fs-7">{{ $book->publisher ?: '-' }}</span>
                                        </div>
                                        <div class="d-flex flex-column text-end">
                                            <span class="text-gray-400 fw-bold fs-9 text-uppercase">ISBN</span>
                                            <span class="fw-bold text-gray-800 fs-7">{{ $book->isbn ?: '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="card border-dashed border-gray-300">
                                <div class="card-body text-center py-15">
                                    <i class="ki-outline ki-book fs-5x text-gray-400 mb-5"></i>
                                    <h3 class="fw-bold text-gray-900 mb-2">Katalog Buku Kosong</h3>
                                    <p class="text-gray-500 fs-6 fw-semibold">Perpustakaan saat ini belum memiliki koleksi buku fisik.</p>
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- TAB 2: MY BORROWINGS --}}
            <div class="tab-pane fade" id="kt_student_borrowings" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-body py-4">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                        <th>Judul Buku</th>
                                        <th>Penulis / Pengarang</th>
                                        <th class="text-center">Tanggal Pinjam</th>
                                        <th class="text-center">Batas Pengembalian</th>
                                        <th class="text-center">Tanggal Pengembalian</th>
                                        <th class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @forelse($myBorrowings as $log)
                                        <tr>
                                            <td class="fw-bold text-gray-900">{{ $log->book->title }}</td>
                                            <td>{{ $log->book->author }}</td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($log->borrowed_at)->format('d M Y') }}</td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($log->due_date)->format('d M Y') }}</td>
                                            <td class="text-center">
                                                {{ $log->returned_at ? \Carbon\Carbon::parse($log->returned_at)->format('d M Y') : '-' }}
                                            </td>
                                            <td class="text-center">
                                                @if($log->status === 'returned')
                                                    <span class="badge badge-light-success fw-bold">Sudah Dikembalikan</span>
                                                @elseif(\Carbon\Carbon::parse($log->due_date)->isPast() && !$log->returned_at)
                                                    <span class="badge badge-light-danger fw-bold">Terlambat</span>
                                                @else
                                                    <span class="badge badge-light-warning fw-bold">Sedang Dipinjam</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-10 text-muted">Anda belum memiliki riwayat peminjaman buku perpustakaan.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
