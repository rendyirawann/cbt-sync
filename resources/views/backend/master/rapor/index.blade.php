@extends('backend.layout.app')
@section('title', 'Raport Hasil Ujian Siswa')

@section('content')
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div id="kt_app_toolbar_container" class="app-container container-fluid px-4 px-lg-6 d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Raport Hasil Ujian</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">Administrasi</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">Akademik</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">Raport Hasil Ujian</li>
            </ul>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-fluid px-4 px-lg-6">

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

        {{-- ======== ROLES CONFIUGURATIONS TAB ======== --}}
        @if(\App\Support\SiklusUjian::pengawas())
            <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold mb-8">
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#kt_rapor_students">
                        Daftar Siswa & Kelas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_rapor_grade_settings">
                        Ketentuan Predikat Rapor
                    </a>
                </li>
            </ul>
        @endif

        <div class="tab-content">
            {{-- TAB 1: LIST STUDENTS --}}
            <div class="tab-pane fade show active" id="kt_rapor_students" role="tabpanel">
                <div class="card shadow-sm border-0 mb-10">
                    <div class="card-header border-0 pt-6">
                        <div class="card-title">
                            <span class="fs-4 fw-bold text-gray-900">Pilih Tahun Ajaran &amp; Rombongan Belajar</span>
                        </div>
                    </div>
                    <div class="card-body">
                        {{-- Satu form, dua dropdown. Mengganti tahun ajaran MENGOSONGKAN
                             pilihan kelas lebih dulu: kelas yang dipilih sebelumnya belum
                             tentu punya anggota di tahun yang baru, dan controller akan
                             memilihkan kelas pertama tahun itu. --}}
                        <form action="{{ route('admin.rapor.index') }}" method="GET" class="row g-3 align-items-end">
                            <div class="col-12 col-md-5">
                                <label class="form-label fw-semibold text-gray-700">Tahun Ajaran</label>
                                <select name="academic_year_id" id="pilihTahunRapor" class="form-select form-select-solid"
                                        onchange="document.getElementById('pilihKelasRapor').value = ''; this.form.submit();">
                                    @forelse($academicYears as $tahun)
                                        <option value="{{ $tahun->id }}" {{ $selectedYearId == $tahun->id ? 'selected' : '' }}>
                                            {{ $tahun->name }} — {{ $tahun->semester }}{{ $tahun->is_active ? ' (aktif)' : '' }}
                                        </option>
                                    @empty
                                        <option value="">Belum ada tahun ajaran</option>
                                    @endforelse
                                </select>
                            </div>
                            <div class="col-12 col-md-5">
                                <label class="form-label fw-semibold text-gray-700">Rombongan Belajar / Kelas</label>
                                <select name="class_room_id" id="pilihKelasRapor" class="form-select form-select-solid"
                                        onchange="this.form.submit()">
                                    @forelse($classRooms as $class)
                                        <option value="{{ $class->id }}" {{ $selectedClassId == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @empty
                                        <option value="">Tidak ada kelas berisi siswa pada tahun ajaran ini</option>
                                    @endforelse
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <span class="text-muted fs-7 d-block pb-2">
                                    {{ count($students) }} siswa
                                </span>
                            </div>
                        </form>
                    </div>
                </div>

                @if($selectedClassId)
                    <div class="card shadow-sm border-0">
                        <div class="card-header border-0 pt-6">
                            <div class="card-title flex-column">
                                <h3 class="fw-bold text-gray-900 mb-1">Daftar Siswa Kelas</h3>
                                {{-- Kotak cari dibuat sendiri: dom DataTables bawaan
                                     Metronic tidak memuat 'f'. --}}
                                <div class="d-flex align-items-center position-relative mt-3">
                                    <i class="ki-outline ki-magnifier fs-4 position-absolute ms-4 text-gray-500"></i>
                                    <input type="text" id="cariSiswaRapor" autocomplete="off"
                                           class="form-control form-control-sm form-control-solid w-100 w-md-300px ps-11"
                                           placeholder="Cari nama, NISN, NIS, atau email">
                                </div>
                            </div>
                        </div>
                        <div class="card-body py-4">
                            <div class="table-responsive">
                                <table id="tabelRaporSiswa" class="table align-middle table-row-dashed fs-6 gy-5">
                                    <thead>
                                        <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                            <th>Nama Lengkap</th>
                                            <th>NISN</th>
                                            <th>NIS</th>
                                            <th class="text-center">Gender</th>
                                            <th class="text-end pe-4">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-600 fw-semibold">
                                        @forelse($students as $student)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="symbol symbol-35px symbol-circle me-3">
                                                            <img src="{{ $student->user->avatar_url }}" alt="">
                                                        </div>
                                                        <div class="d-flex flex-column">
                                                            <span class="text-gray-900 fw-bold">{{ $student->user->name }}</span>
                                                            <span class="text-gray-500 fs-8">{{ $student->user->email }}</span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $student->nisn }}</td>
                                                <td>{{ $student->nis }}</td>
                                                <td class="text-center">
                                                    <span class="badge badge-light fw-bold">{{ $student->gender == 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <a href="{{ route('admin.rapor.show', $student->id) }}" class="btn btn-sm btn-light-primary fw-bold me-2">
                                                        <i class="ki-outline ki-eye fs-4 me-1"></i> Buka Rapor
                                                    </a>
                                                    <a href="{{ route('admin.rapor.generate', $student->id) }}" target="_blank" class="btn btn-sm btn-light-success fw-bold">
                                                        <i class="ki-outline ki-printer fs-4 me-1"></i> Cetak
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-10 text-muted baris-kosong">Belum ada siswa yang terdaftar di kelas ini pada tahun ajaran yang dipilih.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="card border-dashed border-gray-300">
                        <div class="card-body text-center py-15">
                            <i class="ki-outline ki-award fs-5x text-gray-400 mb-5"></i>
                            <h3 class="fw-bold text-gray-900 mb-2">Pilih Kelas Terlebih Dahulu</h3>
                            <p class="text-gray-500 fs-6">Silakan pilih ruang kelas di atas untuk memuat daftar siswa dan memproses Raport Hasil Ujian.</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- TAB 2: GRADE THRESHOLDS SETTINGS --}}
            @if(\App\Support\SiklusUjian::pengawas())
                <div class="tab-pane fade" id="kt_rapor_grade_settings" role="tabpanel">
                    <div class="card shadow-sm border-0 max-w-800px">
                        <div class="card-header border-0 pt-6">
                            <h3 class="card-title fw-bold text-gray-900">Konfigurasi Nilai Huruf & Predikat Raport Hasil Ujian</h3>
                        </div>
                        <form action="{{ route('admin.rapor.settings') }}" method="POST">
                            @csrf
                            <div class="card-body">
                                <p class="text-gray-500 mb-8 fs-6">
                                    Tentukan batas minimum nilai rata-rata bagi siswa untuk mendapatkan huruf mutu predikat (A, B, C, D). Nilai di bawah ambang batas D otomatis dikelompokkan ke dalam predikat E.
                                </p>

                                <div class="row g-9 mb-8">
                                    <div class="col-md-6">
                                        <label class="required fs-6 fw-bold mb-2">Batas Minimum Nilai A (Amat Baik)</label>
                                        <div class="input-group input-group-solid">
                                            <input type="number" name="grade_a" class="form-control" value="{{ $gradeA }}" min="1" max="100" step="0.1" required>
                                            <span class="input-group-text fw-bold">≥ SCORE</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="required fs-6 fw-bold mb-2">Batas Minimum Nilai B (Baik)</label>
                                        <div class="input-group input-group-solid">
                                            <input type="number" name="grade_b" class="form-control" value="{{ $gradeB }}" min="1" max="100" step="0.1" required>
                                            <span class="input-group-text fw-bold">≥ SCORE</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row g-9 mb-8">
                                    <div class="col-md-6">
                                        <label class="required fs-6 fw-bold mb-2">Batas Minimum Nilai C (Cukup)</label>
                                        <div class="input-group input-group-solid">
                                            <input type="number" name="grade_c" class="form-control" value="{{ $gradeC }}" min="1" max="100" step="0.1" required>
                                            <span class="input-group-text fw-bold">≥ SCORE</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="required fs-6 fw-bold mb-2">Batas Minimum Nilai D (Kurang)</label>
                                        <div class="input-group input-group-solid">
                                            <input type="number" name="grade_d" class="form-control" value="{{ $gradeD }}" min="1" max="100" step="0.1" required>
                                            <span class="input-group-text fw-bold">≥ SCORE</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-end bg-light bg-opacity-50 py-6 border-0">
                                <button type="submit" class="btn btn-primary fw-bold">
                                    Simpan Ketentuan Predikat
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>

@push('scripts')
{{-- DataTables tidak ikut di plugins.bundle.js, jadi dimuat sendiri di sini. --}}
<script src="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
<script>
    $(function () {
        var tabel = document.getElementById('tabelRaporSiswa');
        // Tabel kosong = satu sel ber-colspan; DataTables menuntut sel sebanyak
        // kolom di kepala tabel dan akan melempar "Requested unknown parameter".
        if (!tabel || tabel.querySelector('td.baris-kosong')) {
            return;
        }

        var dt = $(tabel).DataTable({
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']],
            order: [[0, 'asc']],      // urut nama
            columnDefs: [
                { orderable: false, searchable: false, targets: [4] }
            ],
            language: {
                lengthMenu: 'Tampilkan _MENU_ baris',
                info: 'Menampilkan _START_–_END_ dari _TOTAL_ siswa',
                infoEmpty: 'Tidak ada siswa',
                infoFiltered: '(disaring dari _MAX_ total)',
                zeroRecords: 'Tidak ada siswa yang cocok dengan pencarian',
                emptyTable: 'Belum ada siswa',
                paginate: { first: 'Awal', last: 'Akhir', next: 'Berikutnya', previous: 'Sebelumnya' }
            }
        });

        var kotakCari = document.getElementById('cariSiswaRapor');
        if (kotakCari) {
            var jeda = null;
            kotakCari.addEventListener('keyup', function () {
                var nilai = this.value;
                clearTimeout(jeda);
                jeda = setTimeout(function () { dt.search(nilai).draw(); }, 250);
            });
            kotakCari.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') { e.preventDefault(); }
            });
        }
    });
</script>
@endpush

@endsection
