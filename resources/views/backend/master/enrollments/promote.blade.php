@extends('backend.layout.app')
@section('title', 'Naik Kelas')

@section('content')
<div class="app-content flex-column-fluid">
    <div class="app-container container-xxl py-6">

        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if($errors->any())
            <div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
        @endif

        <div class="card mb-5">
            <div class="card-header">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">Naik Kelas</h3>
                    <div class="fs-7 text-gray-500">Memindahkan siswa satu rombel ke kelas &amp; tahun ajaran berikutnya</div>
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('enrollments.index') }}" class="btn btn-sm btn-light">Kembali</a>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-light-primary fs-7 py-3 mb-5">
                    Naik kelas <b>menambah</b> penempatan baru untuk tahun ajaran tujuan —
                    rombel tahun asal <b>tidak dihapus</b>. Itulah yang membuat riwayat kelas
                    dan nilai ujian tahun sebelumnya tetap bisa ditelusuri.
                    Di dalam satu tahun ajaran, satu siswa hanya boleh punya satu rombel.
                </div>

                {{-- Langkah 1: pilih asal, daftar siswanya dimuat ulang lewat GET --}}
                <form method="GET" class="row g-3 align-items-end mb-2">
                    <div class="col-md-4">
                        <label class="form-label required fs-7">Tahun ajaran asal</label>
                        <select name="from_academic_year_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Pilih tahun...</option>
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" @selected($dariTahun === $ay->id)>
                                    {{ $ay->name }}{{ $ay->is_active ? ' (aktif)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required fs-7">Kelas asal</label>
                        <select name="from_class_room_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Pilih kelas...</option>
                            @foreach($classRooms as $cr)
                                <option value="{{ $cr->id }}" @selected($dariKelas === $cr->id)>
                                    {{ $cr->name }} — {{ $cr->school->name ?? '-' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 text-muted fs-8">
                        Pilih tahun &amp; kelas asal, daftar siswanya muncul di bawah.
                    </div>
                </form>
            </div>
        </div>

        @if($dariTahun && $dariKelas)
            <form action="{{ route('enrollments.promote') }}" method="POST">
                @csrf
                <input type="hidden" name="from_academic_year_id" value="{{ $dariTahun }}">
                <input type="hidden" name="from_class_room_id" value="{{ $dariKelas }}">

                <div class="card">
                    <div class="card-body">
                        <div class="row g-3 mb-5">
                            <div class="col-md-6">
                                <label class="form-label required fs-7">Tahun ajaran tujuan</label>
                                <select name="to_academic_year_id" class="form-select" required>
                                    <option value="">Pilih tahun...</option>
                                    @foreach($academicYears as $ay)
                                        @if($ay->id !== $dariTahun)
                                            <option value="{{ $ay->id }}">{{ $ay->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <div class="form-text">Harus berbeda dari tahun asal. Untuk sekadar pindah kelas
                                    di tahun yang sama, pakai <b>Plotting Siswa</b>.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required fs-7">Kelas tujuan</label>
                                <select name="to_class_room_id" class="form-select" required>
                                    <option value="">Pilih kelas...</option>
                                    @foreach($classRooms as $cr)
                                        <option value="{{ $cr->id }}">{{ $cr->name }} — {{ $cr->school->name ?? '-' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0 required">Siswa yang dinaikkan ({{ $kandidat->count() }})</label>
                            <label class="form-check form-check-sm">
                                <input class="form-check-input" type="checkbox" id="naikSemua" checked>
                                <span class="form-check-label fs-8 ms-2">Pilih semua</span>
                            </label>
                        </div>

                        <div style="max-height:46vh;overflow:auto">
                            @forelse($kandidat as $cs)
                                <label class="d-flex align-items-center gap-3 border rounded p-3 mb-2">
                                    <input class="form-check-input naik-item" type="checkbox"
                                        name="student_ids[]" value="{{ $cs->student_id }}" checked>
                                    <div>
                                        <div class="fw-semibold text-gray-900 fs-7">{{ $cs->student->user->name ?? '-' }}</div>
                                        <div class="text-muted fs-8">NISN {{ $cs->student->nisn ?: '-' }}</div>
                                    </div>
                                </label>
                            @empty
                                <div class="text-center text-muted py-8">
                                    Tidak ada siswa di kelas &amp; tahun asal itu.
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn-warning" @disabled($kandidat->isEmpty())>
                            <i class="ki-outline ki-arrow-up fs-4 me-1"></i>Naikkan Kelas
                        </button>
                    </div>
                </div>
            </form>
        @endif

    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var semua = document.getElementById('naikSemua');
        if (!semua) return;
        semua.addEventListener('change', function () {
            document.querySelectorAll('.naik-item').forEach(function (c) { c.checked = semua.checked; });
        });
    })();
</script>
@endpush
