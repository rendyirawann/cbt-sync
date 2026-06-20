@extends('backend.layout.app')
@section('title', 'Peserta: ' . $session->name)

@section('content')
<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
    <div class="app-container container-xxl d-flex flex-stack">
        <div class="page-title d-flex flex-column justify-content-center">
            <h1 class="page-heading text-gray-900 fw-bold fs-3 my-0">{{ $session->name }}</h1>
            <span class="text-muted fs-7 pt-1">{{ $session->exam->title }} • {{ $session->exam->teachingAssignment->subject->name ?? '' }}</span>
        </div>
        <a href="{{ route('exams.show', $session->exam_id) }}" class="btn btn-sm btn-light"><i class="ki-outline ki-arrow-left fs-4"></i> Kembali ke Ujian</a>
    </div>
</div>

<div id="kt_app_content" class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        <div class="card">
            <div class="card-body py-4">
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed fs-6 gy-4">
                        <thead><tr class="text-gray-400 fw-bold fs-7 text-uppercase">
                            <th>Siswa</th><th class="text-center">Status</th><th class="text-center">PG (B/S/K)</th>
                            <th class="text-center">Nilai Akhir</th><th class="text-end">Aksi</th>
                        </tr></thead>
                        <tbody class="text-gray-700 fw-semibold">
                            @forelse($eligible as $student)
                            @php $a = $attemptsByStudent->get($student->id); @endphp
                            <tr>
                                <td class="d-flex align-items-center">
                                    <div class="symbol symbol-35px me-3"><img src="{{ $student->user->avatar_url ?? '' }}" class="rounded-circle"></div>
                                    <span class="fw-bold text-gray-900">{{ $student->user->name ?? 'Siswa' }}</span>
                                </td>
                                <td class="text-center">
                                    @if(!$a)<span class="badge badge-light">Belum mulai</span>
                                    @elseif($a->status === 'in_progress')<span class="badge badge-light-info">Sedang mengerjakan</span>
                                    @elseif($a->status === 'submitted')<span class="badge badge-light-warning">Perlu diperiksa</span>
                                    @else<span class="badge badge-light-success">Sudah dinilai</span>@endif
                                </td>
                                <td class="text-center">{{ $a ? "{$a->correct_count}/{$a->wrong_count}/{$a->blank_count}" : '-' }}</td>
                                <td class="text-center">
                                    @if($a && $a->status === 'graded')
                                        <span class="badge badge-{{ (float)$a->final_score >= (float)$session->exam->pass_score ? 'success' : 'danger' }} fs-6">{{ rtrim(rtrim((string)$a->final_score,'0'),'.') }}</span>
                                    @else <span class="text-muted">-</span> @endif
                                </td>
                                <td class="text-end">
                                    @if($a && in_array($a->status, ['submitted','graded']))
                                        <a href="{{ route('exam-attempts.grade', $a->id) }}" class="btn btn-sm btn-light-primary">
                                            <i class="ki-outline ki-{{ $a->status === 'submitted' ? 'notepad-edit' : 'eye' }} fs-5"></i>
                                            {{ $a->status === 'submitted' ? 'Periksa' : 'Lihat' }}
                                        </a>
                                    @else <span class="text-muted fs-7">—</span> @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-10 text-muted">Belum ada peserta pada sesi ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
