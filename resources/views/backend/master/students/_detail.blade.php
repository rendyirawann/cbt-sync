{{-- Detail satu siswa — HANYA bagian dalam .modal-content.

     Sama seperti _form-edit: partial ini TIDAK boleh membawa pembungkus
     .modal / .modal-dialog / .modal-content sendiri. Ia disuntikkan ke dalam
     .modal-content milik modal kerangka di halaman daftar; membawa pembungkus
     sendiri menghasilkan modal bersarang yang display:none, sehingga panelnya
     terbuka tapi kosong.

     Variabel yang diharapkan: $item (Student), $rombel (ClassStudent[]), $riwayat (ExamAttempt[]). --}}
@php
    // Password kartu ujian sengaja TIDAK ditampilkan di sini. Nilainya tersimpan
    // terenkripsi dan bisa dibaca ulang; menampilkannya di modal detail berarti
    // siapa pun yang bisa membuka daftar siswa bisa memakai akun ujian siswa itu.
    // Sandi kartu tetap hanya keluar lewat menu Kartu Ujian.
    $kosong = '<span class="text-muted fw-semibold">belum diisi</span>';
    $baris = function ($label, $nilai) use ($kosong) {
        $isi = trim((string) $nilai) === '' ? $kosong : e($nilai);
        return '<div class="d-flex flex-wrap py-3 border-bottom border-gray-200">'
            . '<div class="text-gray-600 fw-semibold fs-7 min-w-150px">' . e($label) . '</div>'
            . '<div class="text-gray-900 fw-bold fs-6 flex-grow-1">' . $isi . '</div></div>';
    };
@endphp

<div class="modal-header">
    <div>
        <h2 class="fw-bold mb-1">Detail Siswa</h2>
        <div class="text-muted fs-7">Data hanya dapat dilihat. Gunakan tombol Edit untuk mengubah.</div>
    </div>
    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
        <i class="ki-outline ki-cross fs-1 text-dark"></i>
    </div>
</div>

<div class="modal-body">
    {{-- Kepala: identitas ringkas --}}
    <div class="d-flex align-items-center mb-8">
        <div class="symbol symbol-60px symbol-circle me-4">
            <span class="symbol-label bg-light-primary text-primary fw-bold fs-2">
                {{ mb_strtoupper(mb_substr($item->user->name ?? '?', 0, 1)) }}
            </span>
        </div>
        <div class="flex-grow-1">
            <div class="fs-3 fw-bold text-gray-900">{{ $item->user->name ?? '(akun terhapus)' }}</div>
            <div class="text-muted fw-semibold">
                NISN {{ $item->nisn ?: '-' }} &middot; {{ $item->school->name ?? 'tanpa sekolah' }}
            </div>
        </div>
        <div class="text-end">
            @if($item->user)
                <span class="badge badge-light-{{ $item->user->is_active ? 'success' : 'danger' }} fs-7">
                    {{ $item->user->is_active ? 'Akun aktif' : 'Akun nonaktif' }}
                </span>
            @else
                <span class="badge badge-light-danger fs-7">Tanpa akun</span>
            @endif
        </div>
    </div>

    <div class="row g-8">
        <div class="col-lg-6">
            <h5 class="mb-2 text-primary">Informasi Akun</h5>
            {!! $baris('Nama Lengkap', $item->user->name ?? '') !!}
            {!! $baris('Email', $item->user->email ?? '') !!}
            {!! $baris('Username', $item->user->username ?? '') !!}
            {!! $baris('Peran', $item->user ? $item->user->roles->pluck('name')->join(', ') : '') !!}
            {!! $baris('Terakhir Masuk', $item->user?->last_login
                    ? \Carbon\Carbon::parse($item->user->last_login)->translatedFormat('d F Y H:i')
                    : '') !!}
        </div>

        <div class="col-lg-6">
            <h5 class="mb-2 text-primary">Profil Siswa</h5>
            {!! $baris('NISN', $item->nisn) !!}
            {!! $baris('Jenis Kelamin', $item->gender === 'L' ? 'Laki-laki' : ($item->gender === 'P' ? 'Perempuan' : '')) !!}
            {!! $baris('Tempat Lahir', $item->birth_place) !!}
            {!! $baris('Tanggal Lahir', $item->birth_date?->translatedFormat('d F Y')) !!}
            {!! $baris('Telepon', $item->phone) !!}
            {!! $baris('Alamat', $item->address) !!}
        </div>

        <div class="col-lg-6">
            <h5 class="mb-2 text-primary">Data Ujian</h5>
            {!! $baris('Sekolah', $item->school->name ?? '') !!}
            {!! $baris('Gelombang', $item->wave->name ?? '') !!}
            {!! $baris('ID Proktor', $item->proctor_id) !!}
            {!! $baris('Ruang', $item->room) !!}
            <div class="d-flex flex-wrap py-3 border-bottom border-gray-200">
                <div class="text-gray-600 fw-semibold fs-7 min-w-150px">Rombel</div>
                <div class="flex-grow-1">
                    @forelse($rombel as $r)
                        <div class="mb-1">
                            <span class="badge badge-light-info">{{ $r->classRoom->name ?? '-' }}</span>
                            <span class="text-muted fs-8 fw-semibold">{{ $r->academicYear->name ?? 'tanpa tahun ajaran' }}</span>
                        </div>
                    @empty
                        <span class="text-muted fw-semibold">belum terdaftar di rombel mana pun</span>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <h5 class="mb-2 text-primary">Kontak Orang Tua</h5>
            {!! $baris('Nama Orang Tua', $item->parent_name) !!}
            {!! $baris('Email Orang Tua', $item->parent_email) !!}
            {!! $baris('No. WA Orang Tua', $item->parent_phone) !!}
            {!! $baris('Dibuat', $item->created_at?->translatedFormat('d F Y H:i')) !!}
            {!! $baris('Diubah', $item->updated_at?->translatedFormat('d F Y H:i')) !!}
        </div>
    </div>

    {{-- Riwayat ujian: 10 terakhir. Tabel dibungkus .table-responsive karena
         modal di layar sempit lebih sempit lagi daripada halamannya. --}}
    <h5 class="mt-8 mb-3 text-primary border-top pt-5">Riwayat Ujian
        <span class="text-muted fs-7 fw-semibold">(10 terakhir)</span>
    </h5>
    <div class="table-responsive">
        <table class="table table-row-bordered table-row-gray-200 align-middle gs-0 gy-3 mb-0">
            <thead>
                <tr class="fw-bold text-muted fs-7 text-uppercase">
                    <th class="min-w-150px">Ujian</th>
                    <th class="min-w-100px">Mulai</th>
                    <th class="min-w-100px">Status</th>
                    <th class="min-w-75px text-end">Nilai</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayat as $a)
                    <tr>
                        <td class="fw-bold text-gray-900">{{ $a->session->exam->title ?? '(ujian terhapus)' }}</td>
                        <td class="text-muted fw-semibold">
                            {{ $a->started_at ? $a->started_at->translatedFormat('d M Y H:i') : '-' }}
                        </td>
                        <td>
                            @if($a->submitted_at)
                                <span class="badge badge-light-success">Selesai</span>
                            @elseif($a->locked_at)
                                <span class="badge badge-light-danger">Terkunci</span>
                            @elseif($a->started_at)
                                <span class="badge badge-light-warning">Sedang berjalan</span>
                            @else
                                <span class="badge badge-light">Belum mulai</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold">
                            @if($a->final_score !== null)
                                {{ rtrim(rtrim(number_format((float) $a->final_score, 2, ',', '.'), '0'), ',') }}
                            @elseif($a->submitted_at && ! $a->essay_graded)
                                <span class="text-muted fs-8 fw-semibold">menunggu koreksi</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted fw-semibold py-6">
                            Siswa ini belum pernah mengikuti ujian.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal-footer flex-center">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
    <button type="button" class="btn btn-primary btn-edit-siswa ms-3" data-id="{{ $item->id }}"
            data-bs-dismiss="modal">Edit Siswa</button>
</div>
