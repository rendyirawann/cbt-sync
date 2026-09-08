@extends('backend.layout.app')
@section('title', 'Ujian / CBT')

@section('content')
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div class="app-container container-fluid px-4 px-lg-6 d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 my-0">Ujian / CBT</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">Akademik</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">Ujian Online</li>
            </ul>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExamModal">
                <i class="ki-outline ki-plus fs-3"></i> Buat Ujian
            </button>
        </div>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-fluid px-4 px-lg-6">
        <div class="card">
            <div class="card-body py-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                        <thead>
                            <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                <th>Judul Ujian</th>
                                <th>Mata Pelajaran / Kelas</th>
                                <th class="text-center">Tipe</th>
                                <th class="text-center">Soal</th>
                                <th class="text-center">Sesi</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700 fw-semibold">
                            @forelse($exams as $exam)
                            <tr>
                                <td class="fw-bold text-gray-900">{{ $exam->title }}</td>
                                <td>
                                    {{ $exam->teachingAssignment->subject->name ?? '-' }}
                                    <span class="text-muted d-block fs-7">{{ $exam->teachingAssignment->classRoom->name ?? '-' }}</span>
                                </td>
                                <td class="text-center">
                                    @php $tlabel = ['mixed'=>'PG + Essay','mc'=>'Pilihan Ganda','essay'=>'Essay'][$exam->type] ?? $exam->type; @endphp
                                    <span class="badge badge-light-info">{{ $tlabel }}</span>
                                </td>
                                <td class="text-center">{{ $exam->questions_count }}</td>
                                <td class="text-center">{{ $exam->sessions_count }}</td>
                                <td class="text-center">
                                    <span class="badge badge-light-{{ \App\Support\SiklusUjian::warnaStatus($exam->status) }}">
                                        {{ \App\Support\SiklusUjian::labelStatus($exam->status) }}
                                    </span>
                                    @if($exam->isRiwayat())
                                        <div class="text-muted fs-8 mt-1">diarsipkan {{ $exam->archived_at?->translatedFormat('d M Y') }}</div>
                                    @elseif($exam->isSelesai())
                                        <div class="text-muted fs-8 mt-1">selesai {{ $exam->finished_at?->translatedFormat('d M Y') }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('exams.show', $exam->id) }}" class="btn btn-sm btn-light-primary">
                                        <i class="ki-outline ki-setting-3 fs-5"></i> Kelola
                                    </a>
                                    @php
                                        // Ujian yang sudah dikerjakan hanya boleh dihapus Admin, Superadmin,
                                        // atau Developer, karena jawaban & nilai siswa ikut terhapus. Bagi Guru
                                        // tombolnya dinonaktifkan sekalian — dulu tombolnya aktif lalu
                                        // menampilkan dialog penolakan, yang membingungkan.
                                        $adaPengerjaan = (bool) ($exam->sudah_dikerjakan ?? false);
                                        $bolehHapus = !$adaPengerjaan || \App\Support\SiklusUjian::bolehHapusUjianDikerjakan();
                                    @endphp
                                    <form action="{{ route('exams.destroy', $exam->id) }}" method="POST" class="d-inline custom-ajax-confirm">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="hapus_bank" value="0">
                                        <button type="submit" class="btn btn-sm btn-light-danger btn-delete"
                                            data-dikerjakan="{{ $adaPengerjaan ? '1' : '0' }}"
                                            data-bank="{{ (int) ($exam->bank_questions_count ?? 0) }}"
                                            @disabled(!$bolehHapus)
                                            title="{{ $bolehHapus
                                                ? ($adaPengerjaan ? 'Hapus ujian beserta jawaban & nilai siswa (Admin & Superadmin)' : 'Hapus ujian')
                                                : 'Sudah dikerjakan siswa — hanya Admin atau Superadmin yang boleh menghapus' }}"><i class="ki-outline ki-trash fs-5"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center py-10 text-muted">Belum ada ujian. Klik "Buat Ujian" untuk memulai.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== Modal Buat Ujian ===== --}}
<div class="modal fade drawer-modal" id="addExamModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('exams.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h3 class="modal-title">Buat Ujian Baru</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div>
                </div>
                <div class="modal-body px-8 py-8">
                    <div class="mb-5">
                        <label class="form-label required">Mata Pelajaran / Kelas (Penugasan)</label>
                        <select name="teaching_assignment_id" class="form-select" required>
                            <option value="">Pilih penugasan...</option>
                            @foreach($assignments as $ta)
                                <option value="{{ $ta->id }}">{{ $ta->subject->name ?? '-' }} — {{ $ta->classRoom->name ?? '-' }}@isset($ta->teacher) ({{ $ta->teacher->user->name ?? '' }})@endisset</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-5">
                        <label class="form-label required">Judul Ujian</label>
                        <input type="text" name="title" class="form-control" placeholder="cth: Ulangan Harian Bab 1" required>
                    </div>
                    <div class="mb-5">
                        <label class="form-label">Deskripsi / Instruksi</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-5">
                            <label class="form-label required">Kategori Ujian</label>
                            <select name="type" id="cTypeSel" class="form-select" required>
                                <option value="mixed">Pilihan Ganda + Essay</option>
                                <option value="mc" selected>Pilihan Ganda saja</option>
                                <option value="essay">Essay saja</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-5">
                            <label class="form-label required">Mode Penilaian</label>
                            <select name="points_mode" id="cModeSel" class="form-select" required>
                                <option value="auto" selected>Otomatis — poin dibagi rata oleh sistem</option>
                                <option value="manual">Manual — poin tiap soal ditentukan guru</option>
                            </select>
                            <div class="form-text">
                                Poin soal <b>tidak perlu diisi</b> pada kedua mode — sistem membagi rata:
                                PG = 100 ÷ jumlah soal PG (13 soal → 7,69/soal), essay = 100 ÷ jumlah soal essay (7 soal → 14,29/soal).<br>
                                <b>Otomatis</b>: saat memeriksa, guru cukup menandai <b>Benar / Salah</b> tiap essay (benar = poin penuh).<br>
                                <b>Manual</b>: guru menentukan sendiri nilai tiap essay, dengan <b>total seluruh essay maksimal 100</b>.<br>
                                Nilai akhir = <b>(Nilai PG + Nilai Essay) ÷ 2</b>; bila ujian hanya satu jenis soal, bagian itu jadi 100% nilai.
                            </div>
                        </div>
                        <div class="col-md-6 mb-5">
                            <label class="form-label">KKM / Nilai Lulus</label>
                            <input type="number" step="0.01" name="pass_score" class="form-control" value="75">
                        </div>
                    </div>
                    <div class="alert alert-light-primary d-flex align-items-center py-3 mb-0">
                        <i class="ki-outline ki-information-5 fs-2 text-primary me-3"></i>
                        <span class="fs-7">Nilai akhir otomatis berskala <b>0–100</b>: nilai PG dan nilai Essay masing-masing dihitung 0–100, lalu dirata-ratakan.</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Buat & Lanjut Tambah Soal</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Konfirmasi hapus pada form .custom-ajax-confirm (di-skip oleh handler global karena tidak .drawer-modal)
    document.querySelectorAll('.custom-ajax-confirm .btn-delete').forEach(btn => {
        btn.addEventListener('click', function(e){
            e.preventDefault();
            const form = this.closest('form');
            // Peringatan dibuat lebih keras bila ujian itu SUDAH dikerjakan siswa:
            // yang terhapus bukan cuma soal & sesi, tapi jawaban dan nilai mereka.
            // Hanya Superadmin/Developer yang bisa sampai ke titik ini (lihat
            // ExamController::destroy) — bagi Guru/Admin server menolaknya.
            var adaPengerjaan = btn.dataset.dikerjakan === '1';
            var jmlBank = parseInt(btn.dataset.bank || '0', 10);
            var flagBank = form.querySelector('input[name="hapus_bank"]');

            var isi = adaPengerjaan
                ? 'Ujian ini sudah dikerjakan siswa. Menghapusnya juga menghapus <b>jawaban dan nilai</b> mereka, dan tidak bisa dibatalkan.'
                : 'Soal &amp; sesi di dalam ujian ini ikut terhapus.';

            // Bank Soal dipisahkan sebagai PILIHAN, bukan ikut otomatis: isi bank
            // sengaja bertahan agar soalnya bisa dipakai ujian berikutnya.
            if (jmlBank > 0) {
                isi += '<div class="text-start mt-4 fs-7">'
                     + '<b>' + jmlBank + ' soal</b> dari ujian ini juga tersimpan di <b>Bank Soal</b>.'
                     + '<div class="text-muted mt-2">Pilih <b>Hapus ujian saja</b> bila soalnya masih mau dipakai lagi, '
                     + 'atau <b>Hapus + Bank Soal</b> untuk membuang sekalian. '
                     + 'Salinan yang sudah ditarik sekolah lain maupun yang sudah masuk ke ujian lain <b>tidak akan rusak</b> — '
                     + 'salinan itu berdiri sendiri.</div></div>';
            }

            Swal.fire({
                title: adaPengerjaan ? 'Hapus ujian yang SUDAH dikerjakan?' : 'Hapus ujian?',
                html: isi,
                icon: 'warning',
                showCancelButton: true,
                showDenyButton: jmlBank > 0,
                confirmButtonText: jmlBank > 0 ? 'Hapus ujian saja' : (adaPengerjaan ? 'Ya, hapus beserta nilainya' : 'Ya, hapus'),
                denyButtonText: 'Hapus + Bank Soal',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
                denyButtonColor: '#7e1f2e',
                reverseButtons: true
            }).then(function (r) {
                if (!r.isConfirmed && !r.isDenied) return;      // Batal / ditutup
                if (flagBank) flagBank.value = r.isDenied ? '1' : '0';
                form.submit();
            });
        });
    });
</script>
@endpush
@endsection
