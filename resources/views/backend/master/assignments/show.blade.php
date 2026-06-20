@extends(auth()->user()->hasRole('Siswa') ? 'frontend.layout.app' : 'backend.layout.app')
@section('title', 'Detail Penugasan')
@section('content')

<!--begin::Toolbar-->
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <!--begin::Toolbar container-->
    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
        <!--begin::Page title-->
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-gray-900 fw-bold fs-3 flex-column justify-content-center my-0">Detail Penugasan</h1>
            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">
                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Home</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted">Akademik</li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted">
                    <a href="{{ route('assignments.index') }}" class="text-muted text-hover-primary">Penugasan</a>
                </li>
                <li class="breadcrumb-item">
                    <span class="bullet bg-gray-500 w-5px h-2px"></span>
                </li>
                <li class="breadcrumb-item text-muted">Detail</li>
            </ul>
        </div>
        <!--end::Page title-->
        <!--begin::Actions-->
        <div class="d-flex align-items-center gap-2 gap-lg-3">
            <a href="{{ route('assignments.index') }}" class="btn btn-sm fw-bold btn-secondary">
                <i class="ki-outline ki-arrow-left fs-3 me-1"></i> Kembali
            </a>
        </div>
        <!--end::Actions-->
    </div>
</div>
<!--end::Toolbar-->

<div class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        <!-- Assignment Info Header -->
        <div class="card card-flush mb-10 shadow-sm border-0">
            <div class="card-header pt-7">
                <div class="card-title flex-column">
                    <h2 class="fw-bold mb-1">{{ $assignment->title }}</h2>
                    <div class="text-muted fw-semibold fs-6">
                        {{ $assignment->teachingAssignment->subject->name }} - Kelas {{ $assignment->teachingAssignment->classRoom->name }}
                    </div>
                </div>
                <div class="card-toolbar">
                    <span class="badge badge-light-{{ \Carbon\Carbon::parse($assignment->due_date)->isPast() ? 'danger' : 'primary' }} fs-7 fw-bold">
                        Deadline: {{ \Carbon\Carbon::parse($assignment->due_date)->format('d M Y, H:i') }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="fs-6 text-gray-700 mb-5">
                    {!! nl2br(e($assignment->description)) !!}
                </div>
                @if($assignment->file_path)
                    <div class="d-flex align-items-center p-3 border border-dashed border-gray-300 rounded bg-light">
                        <i class="ki-outline ki-file-down fs-2x text-primary me-4"></i>
                        <div class="flex-grow-1">
                            <span class="text-gray-800 fw-bold d-block">Lampiran Instruksi</span>
                            <span class="text-muted fs-7">Klik untuk mengunduh instruksi tugas</span>
                        </div>
                        <a href="{{ Storage::url($assignment->file_path) }}" target="_blank" class="btn btn-sm btn-primary">Download PDF</a>
                    </div>
                @endif
            </div>
        </div>

        @hasanyrole('Superadmin|Guru')
        <!-- Teacher View: Submission List -->
        <div class="card card-flush shadow-sm border-0">
            <div class="card-header pt-7">
                <h3 class="card-title fw-bold text-gray-800">Daftar Pengumpulan Siswa</h3>
                <div class="card-toolbar">
                    <span class="badge badge-light-success fs-7 fw-bold">{{ $assignment->submissions->count() }} Terkumpul</span>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-5 align-middle fw-bold">
                        <thead class="fs-7 text-gray-400 text-uppercase">
                            <tr>
                                <th class="min-w-200px">Nama Siswa</th>
                                <th>Tanggal Kirim</th>
                                <th>File Jawaban</th>
                                <th>Nilai</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="fs-6">
                            @foreach($students as $s)
                                @php
                                    $submission = $assignment->submissions->where('student_id', $s->student->id)->first();
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-35px symbol-circle me-3">
                                                <span class="symbol-label bg-light-primary text-primary">{{ substr($s->student->user->name, 0, 1) }}</span>
                                            </div>
                                            <div class="d-flex justify-content-start flex-column">
                                                <span class="text-gray-800 fw-bold fs-6">{{ $s->student->user->name }}</span>
                                                <span class="text-muted fw-semibold d-block fs-7">NISN: {{ $s->student->nisn }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($submission)
                                            {{ \Carbon\Carbon::parse($submission->submitted_at)->format('d M Y, H:i') }}
                                        @else
                                            <span class="text-danger">Belum Mengumpulkan</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($submission && $submission->file_path)
                                            <a href="{{ Storage::url($submission->file_path) }}" target="_blank" class="btn btn-sm btn-light-info">
                                                <i class="ki-outline ki-document fs-7"></i> Lihat Jawaban
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($submission)
                                            <span class="badge badge-{{ $submission->score >= 75 ? 'success' : 'warning' }} fs-6">
                                                {{ $submission->score ?? 'Belum Dinilai' }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if($submission)
                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#scoreModal{{ $submission->id }}">Nilai</button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endhasanyrole

        @hasrole('Siswa')
        <!-- Student View: My Submission -->
        @php
            $mySubmission = $assignment->submissions->where('student_id', auth()->user()->student->id)->first();
        @endphp
        <div class="card card-flush shadow-sm border-0">
            <div class="card-header pt-7">
                <h3 class="card-title fw-bold text-gray-800">Pengumpulan Saya</h3>
            </div>
            <div class="card-body">
                @if($mySubmission)
                    <div class="row">
                        <div class="col-md-6">
                            <div class="p-5 border rounded bg-light mb-5">
                                <label class="fw-bold text-gray-600 mb-2">Jawaban Anda:</label>
                                <div class="d-flex align-items-center">
                                    <i class="ki-outline ki-file-text fs-2x text-success me-3"></i>
                                    <a href="{{ Storage::url($mySubmission->file_path) }}" target="_blank" class="fw-bold text-gray-800">Lihat File Jawaban</a>
                                </div>
                                <div class="fs-7 text-muted mt-2">Dikirim pada: {{ \Carbon\Carbon::parse($mySubmission->submitted_at)->format('d M Y, H:i') }}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-5 border rounded bg-light-success h-100">
                                <label class="fw-bold text-success mb-2">Nilai Tugas:</label>
                                <div class="fs-2qx fw-bolder text-gray-900">{{ $mySubmission->score ?? '-' }} / 100</div>
                                @if($mySubmission->feedback)
                                    <div class="mt-3">
                                        <label class="fw-bold text-gray-600 fs-7">Catatan Guru:</label>
                                        <p class="text-gray-800 italic fs-7">{{ $mySubmission->feedback }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    @if(\Carbon\Carbon::parse($assignment->due_date)->isPast())
                        <div class="alert alert-danger">Batas waktu pengumpulan telah berakhir. Silakan hubungi guru Anda.</div>
                    @else
                        <form action="{{ route('assignments.submit', $assignment->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="fv-row mb-7">
                                <label class="required fs-6 fw-semibold mb-2">Unggah Jawaban (PDF/ZIP)</label>
                                <input type="file" name="file" class="form-control form-control-solid" required />
                            </div>
                            <div class="fv-row mb-7">
                                <label class="fs-6 fw-semibold mb-2">Catatan Tambahan</label>
                                <textarea name="student_note" class="form-control form-control-solid" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Kirim Tugas Sekarang</button>
                        </form>
                    @endif
                @endif
            </div>
        </div>
        @endhasrole
    </div>
</div>

@hasanyrole('Superadmin|Guru')
    @foreach($assignment->submissions as $sub)
    <div class="modal fade" id="scoreModal{{ $sub->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('assignments.score', $sub->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h2 class="fw-bold">Beri Nilai: {{ $sub->student->user->name }}</h2>
                    </div>
                    <div class="modal-body">
                        <div class="fv-row mb-7">
                            <label class="required fs-6 fw-semibold mb-2">Nilai (0-100)</label>
                            <input type="number" name="score" class="form-control form-control-solid" value="{{ $sub->score }}" required />
                        </div>
                        <div class="fv-row mb-7">
                            <label class="fs-6 fw-semibold mb-2">Feedback / Komentar</label>
                            <textarea name="feedback" class="form-control form-control-solid" rows="3">{{ $sub->feedback }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="submit" class="btn btn-primary">Simpan Nilai</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach
@endhasanyrole

@endsection
