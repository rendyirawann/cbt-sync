@extends('backend.layout.app')
@section('title', 'Rombongan Belajar')
@section('content')

@include('partials.kop-halaman', [
    'judul' => 'Rombongan Belajar',
    'jejak' => [
        ['label' => 'Data Master'],
        ['label' => 'Rombel / Kelas', 'route' => 'class-rooms.index'],
        ['label' => 'Rombongan Belajar', 'route' => 'enrollments.index'],
    ],
])
<div class="app-content flex-column-fluid">
    <div class="app-container container-fluid px-4 px-lg-6">
        <div class="card card-flush mt-6 mt-xl-9">
            <div class="card-header mt-5">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">Plotting Siswa (Rombel)</h3>
                    <div class="fs-6 text-gray-500">Manajemen penempatan siswa ke dalam kelas</div>
                </div>
                <div class="card-toolbar gap-2">
                    <form method="GET" class="d-flex align-items-center gap-2 me-2">
                        <span class="text-muted fs-8">Tahun ajaran</span>
                        <select name="academic_year_id" class="form-select form-select-sm w-200px" onchange="this.form.submit()">
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" @selected($tahunId === $ay->id)>
                                    {{ $ay->name }}{{ $ay->is_active ? ' (aktif)' : '' }}</option>
                            @endforeach
                        </select>
                    </form>
                    <a href="{{ route('enrollments.promote.form') }}" class="btn btn-sm btn-light-warning">
                        <i class="ki-outline ki-arrow-up fs-5 me-1"></i>Naik Kelas
                    </a>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Plotting Siswa Baru</button>
                </div>
            </div>
            <div class="card-body pt-0">
                {{-- Dikelompokkan per KELAS: satu kartu = satu rombel pada tahun ajaran
                     terpilih. Daftar siswanya dibuka lewat tombol, supaya halaman ini
                     tidak lagi berupa daftar siswa memanjang. --}}
                @forelse($enrollments as $classRoomId => $anggota)
                    @php $kelas = $anggota->first()->classRoom; @endphp
                    <div class="card border mb-3" id="grupRombel{{ $loop->index }}wrap">
                        <div class="card-body py-4">
                            <div class="d-flex flex-stack flex-wrap gap-3">
                                <div>
                                    <div class="fw-bold fs-5 text-gray-900">{{ $kelas->name ?? 'Kelas terhapus' }}</div>
                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        <span class="badge badge-light-dark">{{ $kelas->school->name ?? '-' }}</span>
                                        <span class="badge badge-light-primary">{{ $anggota->count() }} siswa</span>
                                        <span class="badge badge-light-warning">{{ $anggota->first()->academicYear->name ?? '-' }}</span>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    @if($kelas)
                                        <a href="{{ route('class-rooms.students', $kelas->id) }}?academic_year_id={{ $tahunId }}"
                                            class="btn btn-sm btn-light">Halaman kelas</a>
                                    @endif
                                    <button class="btn btn-sm btn-light-primary" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#grupRombel{{ $loop->index }}">
                                        <i class="ki-outline ki-eye fs-5 me-1"></i>Lihat {{ $anggota->count() }} siswa
                                    </button>
                                    @if($kelas)
                                        {{-- Mencetak kartu bisa menerbitkan password baru, jadi POST + konfirmasi. --}}
                                        <form action="{{ route('class-rooms.cards', $kelas->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="academic_year_id" value="{{ $tahunId }}">
                                            <button type="submit" class="btn btn-sm btn-light-danger confirm-kartu">
                                                <i class="ki-outline ki-file-down fs-5 me-1"></i>Export PDF Kartu Ujian
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                            <div class="collapse mt-4" id="grupRombel{{ $loop->index }}">
                                <div class="table-responsive">
                                    {{-- Satu tabel per rombel; DataTables dipasang ke semuanya
                                         lewat kelas .tabel-rombel. Kolom nomor & Aksi tidak
                                         diurutkan/dicari. --}}
                                    <table class="table table-row-dashed align-middle gy-2 tabel-rombel">
                                        <thead>
                                            <tr class="fw-bold text-muted fs-8 text-uppercase">
                                                <th style="width:46px">#</th><th>Siswa</th><th>NISN</th>
                                                <th>Sekolah Asal</th><th class="text-end">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($anggota as $n => $item)
                                                <tr>
                                                    <td class="text-muted">{{ $n + 1 }}</td>
                                                    <td class="fw-semibold text-gray-900">{{ $item->student->user->name ?? '-' }}</td>
                                                    <td>{{ $item->student->nisn ?: '-' }}</td>
                                                    <td class="text-muted fs-7">{{ $item->classRoom->school->name ?? '-' }}</td>
                                                    <td class="text-end">
                                                        <form action="{{ route('enrollments.destroy', $item->id) }}" method="POST" class="d-inline">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="btn btn-icon btn-sm btn-light-danger confirm-delete"
                                                                title="Keluarkan dari rombel ini">
                                                                <i class="ki-outline ki-trash fs-3"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center px-4 py-15">
                        <h3 class="fw-bold text-gray-900 mb-2">Belum ada rombongan belajar</h3>
                        <p class="text-gray-400 fs-6 fw-semibold">Belum ada siswa yang diplot pada tahun ajaran ini.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade drawer-modal" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('enrollments.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h2 class="fw-bold">Plotting Siswa Massal</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1 text-dark"></i>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="row mb-7">
                        <div class="col-md-6">
                            <label class="required fs-6 fw-semibold mb-2">Tahun Ajaran</label>
                            <select name="academic_year_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#addModal" required>
                                @foreach($academicYears as $ay)
                                    <option value="{{ $ay->id }}" {{ $activeAY && $activeAY->id == $ay->id ? 'selected' : '' }}>{{ $ay->name }} (Sem {{ $ay->semester }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required fs-6 fw-semibold mb-2">Pilih Kelas Tujuan</label>
                            <select name="class_room_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#addModal" required>
                                <option value="">Pilih Kelas...</option>
                                @foreach($classRooms as $cr)
                                    <option value="{{ $cr->id }}">{{ $cr->name }} ({{ $cr->school->name ?? '-' }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Pilih Siswa</label>
                        @include('backend.master._pilih-siswa', [
                            'uid' => 'psPlot',
                            'name' => 'student_ids[]',
                            'kosong' => 'Semua siswa sudah punya rombel pada tahun ajaran ini.',
                        ])
                        <div class="text-muted fs-7 mt-2">Menampilkan siswa yang belum memiliki kelas di tahun ajaran terpilih.
                            <b>Pilih semua</b>/<b>Kosongkan</b> berlaku pada daftar yang sedang tampil, dan klik sambil menahan <b>Shift</b> mencentang satu rentang sekaligus.</div>
                    </div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Plotting Siswa</button>
                </div>
            </form>
        </div>
    </div>
</div>


@push('scripts')
{{-- Bundel DataTables TIDAK ada di plugins.bundle.js; halaman yang memakainya
     harus memuatnya sendiri. Tanpa ini $().DataTable undefined, pemanggilannya
     melempar galat, dan SELURUH skrip di bawahnya berhenti jalan. --}}
<script src="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
<script>
    $(function () {
        // Daftar anggota rombel bisa puluhan siswa per kelas dan halaman ini
        // memuat SEMUA rombel sekaligus, jadi tanpa paging halamannya memanjang.
        $('.tabel-rombel').each(function () {
            var t = $(this);
            if (t.find('tbody tr').length < 8) return;   // rombel kecil tidak perlu paging
            t.DataTable({
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Semua']],
                order: [[1, 'asc']],
                columnDefs: [{ orderable: false, searchable: false, targets: [0, -1] }],
                language: {
                    search: 'Cari:',
                    searchPlaceholder: 'nama / NISN',
                    lengthMenu: 'Tampilkan _MENU_',
                    info: '_START_–_END_ dari _TOTAL_ siswa',
                    infoEmpty: 'Tidak ada siswa',
                    infoFiltered: '(disaring dari _MAX_)',
                    zeroRecords: 'Tidak ada siswa yang cocok',
                    paginate: { first: 'Awal', last: 'Akhir', next: 'Berikutnya', previous: 'Sebelumnya' }
                },
                // Nomor urut dijaga tetap 1..n mengikuti halaman yang tampil,
                // bukan angka asli baris — kalau tidak, urutannya membingungkan
                // setelah pencarian.
                drawCallback: function () {
                    var mulai = this.api().page.info().start;
                    this.api().column(0, { page: 'current' }).nodes().each(function (sel, i) {
                        sel.innerHTML = mulai + i + 1;
                    });
                }
            });
        });
    });
</script>
<script>
    // Kartu ujian: jelaskan efek penerbitan password sebelum mencetak.
    document.querySelectorAll('.confirm-kartu').forEach(function (b) {
        b.addEventListener('click', function (e) {
            e.preventDefault();
            var form = this.closest('form');
            Swal.fire({
                title: 'Cetak kartu ujian rombel ini?',
                html: 'Kartu memuat <b>username &amp; password</b> peserta.<br><br>'
                    + 'Siswa yang <b>belum punya password kartu</b> akan diterbitkan password baru, '
                    + 'dan password itu menggantikan password akunnya. Siswa yang sudah punya '
                    + '<b>tidak diubah</b> — mencetak ulang menghasilkan password yang sama.',
                icon: 'info', showCancelButton: true,
                confirmButtonText: 'Ya, cetak PDF', cancelButtonText: 'Batal'
            }).then(function (r) { if (r.isConfirmed) form.submit(); });
        });
    });

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