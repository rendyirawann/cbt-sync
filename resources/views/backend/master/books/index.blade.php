@extends('backend.layout.app')
@section('title', 'Manajemen Perpustakaan & Inventaris')

@section('content')
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Manajemen Inventaris Perpustakaan</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">Administrasi</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">Perpustakaan</li>
            </ul>
        </div>
        <div class="d-flex align-items-center gap-2 gap-lg-3">
            <button type="button" class="btn btn-sm fw-bold btn-success" data-bs-toggle="modal" data-bs-target="#borrowModal">
                <i class="ki-outline ki-exit-right fs-4 me-1"></i> Peminjaman Baru
            </button>
            <button type="button" class="btn btn-sm fw-bold btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                <i class="ki-outline ki-plus fs-4 me-1"></i> Tambah Buku
            </button>
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
        @if(session('error'))
            <div class="alert alert-danger d-flex align-items-center p-5 mb-10">
                <i class="ki-outline ki-shield-cross fs-2hx text-danger me-4"></i>
                <div class="d-flex flex-column">
                    <h4 class="mb-1 text-dark fw-bold">Gagal!</h4>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        {{-- ======== TAB NAVIGATION ======== --}}
        <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold mb-8">
            <li class="nav-item font-size-lg">
                <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#kt_library_books">
                    Katalog Buku ({{ $books->count() }})
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_library_borrowings">
                    Log Peminjaman & Pengembalian ({{ $borrowings->count() }})
                </a>
            </li>
        </ul>

        {{-- ======== TAB CONTENT ======== --}}
        <div class="tab-content" id="myTabContent">
            {{-- TAB 1: BOOK CATALOG --}}
            <div class="tab-pane fade show active" id="kt_library_books" role="tabpanel">
                <div class="row g-6 g-xl-9">
                    @forelse($books as $book)
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="card h-100 card-custom border-0 shadow-sm overflow-hidden position-relative">
                                @if($book->available_stock == 0)
                                    <span class="position-absolute top-0 end-0 badge badge-danger m-4 z-index-2">HABIS</span>
                                @else
                                    <span class="position-absolute top-0 end-0 badge badge-success m-4 z-index-2">{{ $book->available_stock }} / {{ $book->stock }} TERSEDIA</span>
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

                                {{-- Footer Actions --}}
                                <div class="card-footer p-3 bg-light bg-opacity-50 border-0 d-flex gap-2 justify-content-end">
                                    <button class="btn btn-sm btn-icon btn-light-warning" data-bs-toggle="modal" data-bs-target="#editBookModal{{ $book->id }}" title="Edit Buku">
                                        <i class="ki-outline ki-pencil fs-4"></i>
                                    </button>
                                    <form action="{{ route('books.destroy', $book->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-icon btn-light-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus buku ini dari katalog perpustakaan?')">
                                            <i class="ki-outline ki-trash fs-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="card border-dashed border-gray-300">
                                <div class="card-body text-center py-15">
                                    <i class="ki-outline ki-book fs-5x text-gray-400 mb-5"></i>
                                    <h3 class="fw-bold text-gray-900 mb-2">Katalog Buku Kosong</h3>
                                    <p class="text-gray-500 fs-6 fw-semibold mb-5">Saat ini belum ada buku fisik yang ditambahkan ke inventaris perpustakaan.</p>
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBookModal">
                                        Tambah Buku Pertama
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- TAB 2: BORROWINGS LOG --}}
            <div class="tab-pane fade" id="kt_library_borrowings" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-body py-4">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                <thead>
                                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                        <th>Siswa Peminjam</th>
                                        <th>Judul Buku</th>
                                        <th class="text-center">Tgl Pinjam</th>
                                        <th class="text-center">Batas Kembali</th>
                                        <th class="text-center">Tgl Kembali</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-end pe-4">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-600 fw-semibold">
                                    @forelse($borrowings as $log)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="symbol symbol-35px symbol-circle me-3">
                                                        <img src="{{ $log->student->user->avatar_url }}" alt="">
                                                    </div>
                                                    <div class="d-flex flex-column">
                                                        <span class="text-gray-900 fw-bold">{{ $log->student->user->name }}</span>
                                                        <span class="text-gray-500 fs-8">NISN: {{ $log->student->nisn }}</span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="fw-bold text-gray-900">{{ $log->book->title }}</td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($log->borrowed_at)->format('d M Y') }}</td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($log->due_date)->format('d M Y') }}</td>
                                            <td class="text-center">
                                                {{ $log->returned_at ? \Carbon\Carbon::parse($log->returned_at)->format('d M Y') : '-' }}
                                            </td>
                                            <td class="text-center">
                                                @if($log->status === 'returned')
                                                    <span class="badge badge-light-success fw-bold">Kembali</span>
                                                @elseif(\Carbon\Carbon::parse($log->due_date)->isPast() && !$log->returned_at)
                                                    <span class="badge badge-light-danger fw-bold">Terlambat</span>
                                                @else
                                                    <span class="badge badge-light-warning fw-bold">Dipinjam</span>
                                                @endif
                                            </td>
                                            <td class="text-end pe-4">
                                                @if(!$log->returned_at)
                                                    <form action="{{ route('borrowings.return', $log->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-light-success fw-bold px-4">
                                                            Kembalikan
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-muted fs-7">Selesai ✓</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-10 text-muted">Belum ada riwayat peminjaman buku perpustakaan.</td>
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

{{-- ADD BOOK MODAL --}}
<div class="modal fade" id="addBookModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <form action="{{ route('books.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h2 class="fw-bold">Tambah Buku Baru</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body px-10 py-10">
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Judul Buku</label>
                        <input type="text" name="title" class="form-control form-control-solid" required>
                    </div>
                    <div class="row g-9 mb-7">
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">Penulis / Pengarang</label>
                            <input type="text" name="author" class="form-control form-control-solid" required>
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="fs-6 fw-semibold mb-2">Penerbit</label>
                            <input type="text" name="publisher" class="form-control form-control-solid">
                        </div>
                    </div>
                    <div class="row g-9 mb-7">
                        <div class="col-md-6 fv-row">
                            <label class="fs-6 fw-semibold mb-2">ISBN</label>
                            <input type="text" name="isbn" class="form-control form-control-solid">
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">Jumlah Stok Fisik</label>
                            <input type="number" name="stock" class="form-control form-control-solid" value="1" min="1" required>
                        </div>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="fs-6 fw-semibold mb-2">Cover Buku (Opsional)</label>
                        <input type="file" name="cover" class="form-control form-control-solid" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="submit" class="btn btn-primary">Simpan Buku</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- EDIT BOOK MODALS --}}
@foreach($books as $book)
<div class="modal fade" id="editBookModal{{ $book->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <form action="{{ route('books.update', $book->id) }}" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h2 class="fw-bold">Edit Buku Perpustakaan</h2>
                </div>
                <div class="modal-body px-10 py-10">
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Judul Buku</label>
                        <input type="text" name="title" class="form-control form-control-solid" value="{{ $book->title }}" required>
                    </div>
                    <div class="row g-9 mb-7">
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">Penulis / Pengarang</label>
                            <input type="text" name="author" class="form-control form-control-solid" value="{{ $book->author }}" required>
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="fs-6 fw-semibold mb-2">Penerbit</label>
                            <input type="text" name="publisher" class="form-control form-control-solid" value="{{ $book->publisher }}">
                        </div>
                    </div>
                    <div class="row g-9 mb-7">
                        <div class="col-md-6 fv-row">
                            <label class="fs-6 fw-semibold mb-2">ISBN</label>
                            <input type="text" name="isbn" class="form-control form-control-solid" value="{{ $book->isbn }}">
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">Jumlah Stok Fisik</label>
                            <input type="number" name="stock" class="form-control form-control-solid" value="{{ $book->stock }}" min="0" required>
                        </div>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="fs-6 fw-semibold mb-2">Cover Buku Baru (Opsional)</label>
                        <input type="file" name="cover" class="form-control form-control-solid" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="submit" class="btn btn-primary">Update Buku</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

{{-- REGISTER BORROW MODAL --}}
<div class="modal fade" id="borrowModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-600px">
        <div class="modal-content">
            <form action="{{ route('borrowings.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h2 class="fw-bold">Peminjaman Buku Fisik Baru</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1"></i>
                    </div>
                </div>
                <div class="modal-body px-10 py-10">
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Pilih Buku Fisik</label>
                        <select name="book_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#borrowModal" required>
                            <option value="">Pilih...</option>
                            @foreach($books as $b)
                                @if($b->available_stock > 0)
                                    <option value="{{ $b->id }}">{{ $b->title }} (Stok Tersedia: {{ $b->available_stock }})</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Pilih Siswa Peminjam</label>
                        <select name="student_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#borrowModal" required>
                            <option value="">Pilih...</option>
                            @foreach($students as $s)
                                <option value="{{ $s->id }}">{{ $s->user->name }} (NISN: {{ $s->nisn }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-9 mb-7">
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">Tanggal Pinjam</label>
                            <input type="date" name="borrowed_at" class="form-control form-control-solid" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6 fv-row">
                            <label class="required fs-6 fw-semibold mb-2">Batas Waktu Pengembalian</label>
                            <input type="date" name="due_date" class="form-control form-control-solid" value="{{ date('Y-m-d', strtotime('+7 days')) }}" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="submit" class="btn btn-success">Daftarkan Peminjaman</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
