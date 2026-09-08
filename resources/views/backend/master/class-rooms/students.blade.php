@extends('backend.layout.app')
@section('title', 'Siswa Kelas ' . $classRoom->name)

@section('content')
{{-- Parameter aksi dirender kop sebagai HTML mentah, jadi stringnya dirakit
     dari route() saja: jangan pernah menyisipkan data yang diisi pengguna. --}}
@include('partials.kop-halaman', [
    'judul' => 'Siswa Kelas ' . $classRoom->name,
    'catatan' => $classRoom->school->name ?? '-',
    'aksi' => '<a href="' . route('class-rooms.index') . '" class="btn btn-sm btn-light">Kembali</a>',
    'jejak' => [
        ['label' => 'Data Master'],
        ['label' => 'Rombel / Kelas', 'route' => 'class-rooms.index'],
        ['label' => 'Siswa Kelas'],
    ],
])

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-fluid px-4 px-lg-6">

        <div class="card mb-5"><div class="card-body py-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fs-8">Tahun Ajaran</label>
                    <select name="academic_year_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach($academicYears as $ay)
                            <option value="{{ $ay->id }}" @selected($tahunId === $ay->id)>
                                {{ $ay->name }}{{ $ay->is_active ? ' (aktif)' : '' }}
                                — {{ $riwayat[$ay->id] ?? 0 }} siswa
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8">
                    <div class="text-muted fs-8">
                        Keanggotaan rombel dicatat <b>per tahun ajaran</b>. Memilih tahun lain menampilkan
                        anggota kelas ini pada tahun tersebut — riwayatnya tidak hilang saat siswa naik kelas.
                    </div>
                </div>
            </form>
        </div></div>

        <div class="card">
            <div class="card-header">
                <div class="card-title"><h3 class="fw-bold m-0">{{ $anggota->count() }} Siswa</h3></div>
                <div class="card-toolbar">
                    <a href="{{ route('enrollments.index') }}" class="btn btn-sm btn-light-primary">
                        <i class="ki-outline ki-plus fs-5 me-1"></i>Plot Siswa
                    </a>
                </div>
            </div>
            <div class="card-body pt-2">
                <div class="table-responsive">
                    <table class="table table-row-dashed align-middle gy-3">
                        <thead>
                            <tr class="fw-bold text-muted fs-8 text-uppercase">
                                <th style="width:50px">#</th>
                                <th>Nama</th><th>NISN</th><th>Jenis Kelamin</th><th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($anggota as $i => $cs)
                                <tr>
                                    <td class="text-muted">{{ $i + 1 }}</td>
                                    <td class="fw-semibold text-gray-900">{{ $cs->student->user->name ?? '-' }}</td>
                                    <td>{{ $cs->student->nisn ?: '-' }}</td>
                                    <td>{{ $cs->student->gender ?: '-' }}</td>
                                    <td class="text-muted fs-7">{{ $cs->student->user->email ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-10">
                                    Belum ada siswa di kelas ini pada tahun ajaran tersebut.
                                    Tambahkan lewat <b>Plotting Siswa (Rombel)</b>.
                                </td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
