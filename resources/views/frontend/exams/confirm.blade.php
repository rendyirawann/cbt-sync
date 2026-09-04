@extends('frontend.layout.app')
@section('title', 'Konfirmasi Peserta: ' . $session->exam->title)

@section('content')
@php
    $exam = $session->exam;
    // Jumlah soal yang akan diterima siswa ini. Pada mode otomatis tiap siswa
    // hanya menerima sebagian soal (acak), jadi angkanya bukan total soal ujian.
    $aktif = $exam->question_selection === 'all'
        ? $exam->questions->count()
        : $exam->questions->where('is_active', true)->count();
    $jumlahSoal = $exam->question_selection === 'auto' && $exam->active_question_count
        ? min((int) $exam->active_question_count, $aktif)
        : $aktif;
@endphp

<div class="app-content flex-column-fluid">
    <div class="app-container container-xxl py-6">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                    </div>
                @endif

                <div class="card mb-5">
                    <div class="card-header">
                        <div class="card-title">
                            <h2 class="fw-bold m-0">Konfirmasi Peserta Ujian</h2>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-light-primary fs-7 py-3 mb-6">
                            Periksa data Anda, lalu tanda tangani daftar hadir di bawah.
                            Setelah menekan <b>Mulai Ujian</b>, Anda dinyatakan <b>hadir</b>,
                            waktu ujian langsung berjalan, dan soal tidak dapat diulang.
                        </div>

                        <h4 class="fw-bold mb-3">Data Siswa</h4>
                        <div class="row mb-6">
                            <div class="col-md-6 mb-3">
                                <div class="text-muted fs-8">Nama</div>
                                <div class="fw-bold fs-6">{{ $student->user->name ?? '-' }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="text-muted fs-8">NISN</div>
                                <div class="fw-bold fs-6">{{ $student->nisn ?: '-' }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="text-muted fs-8">Kelas</div>
                                <div class="fw-bold fs-6">{{ $kelas->name ?? '-' }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="text-muted fs-8">Email</div>
                                <div class="fw-bold fs-6">{{ $student->user->email ?? '-' }}</div>
                            </div>
                        </div>

                        <h4 class="fw-bold mb-3">Data Ujian</h4>
                        <div class="row mb-2">
                            <div class="col-md-6 mb-3">
                                <div class="text-muted fs-8">Ujian</div>
                                <div class="fw-bold fs-6">{{ $exam->title }}</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="text-muted fs-8">Mata Pelajaran</div>
                                <div class="fw-bold fs-6">{{ $exam->teachingAssignment->subject->name ?? '-' }}</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="text-muted fs-8">Sesi</div>
                                <div class="fw-bold fs-6">{{ $session->name }}</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="text-muted fs-8">Durasi</div>
                                <div class="fw-bold fs-6">{{ $session->duration_minutes }} menit</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="text-muted fs-8">Jumlah Soal</div>
                                <div class="fw-bold fs-6">{{ $jumlahSoal }} soal
                                    @if($exam->question_selection === 'auto')
                                        <span class="badge badge-light-warning ms-1">acak</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <form action="{{ route('student.exams.confirm.store', $session->id) }}" method="POST" id="formKonfirmasi">
                    @csrf
                    <div class="card">
                        <div class="card-body">
                            <label class="form-check mb-5">
                                <input class="form-check-input" type="checkbox" name="agree" value="1" id="ttdSetuju">
                                <span class="form-check-label ms-2">Saya menyatakan data di atas benar dan siap
                                    mengikuti ujian ini dengan jujur.</span>
                            </label>

                            <div class="alert alert-light-warning fs-8 py-3 mb-5">
                                Tanda tangan daftar hadir diminta <b>di akhir</b>, saat Anda mengumpulkan ujian.
                            </div>

                            <div class="d-flex gap-3">
                                <a href="{{ route('student.exams.index') }}" class="btn btn-light">Kembali</a>
                                <button type="submit" class="btn btn-primary flex-grow-1" id="ttdKirim">
                                    <i class="ki-outline ki-rocket fs-4 me-1"></i>Mulai Ujian
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('formKonfirmasi').addEventListener('submit', function (e) {
        if (!document.getElementById('ttdSetuju').checked) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Belum dicentang', text: 'Centang pernyataan bahwa data Anda benar.' });
        }
    });
</script>
@endpush
