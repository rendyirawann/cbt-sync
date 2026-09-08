<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Student;
use App\Support\SiklusUjian;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan angka untuk Dashboard — khusus hal yang berkaitan dengan CBT.
 *
 * Semua angka dihitung dari database, bukan nilai contoh. Dikumpulkan di satu
 * kelas supaya dashboard admin dan dashboard siswa tidak masing-masing menulis
 * query sendiri lalu berbeda hasilnya.
 *
 * Prinsip yang dipegang di sini:
 *  - Hitung dengan agregat SQL, bukan mengambil koleksi lalu count() di PHP.
 *    Dashboard adalah halaman pertama yang dibuka setiap orang; ia tidak boleh
 *    memuat ribuan baris hanya untuk menampilkan satu angka.
 *  - Batasan sekolah mengikuti relasi yang sama dengan halaman Ujian
 *    (teachingAssignment.classRoom.school_id), supaya angkanya konsisten dengan
 *    daftar yang dilihat pengguna.
 */
class RingkasanCbt
{
    /** Ringkasan untuk Admin/Superadmin/Developer/Kepala Sekolah & Guru. */
    public function untukPengelola($user, ?string $schoolId): array
    {
        $ujian = Exam::query()
            ->when($schoolId, fn ($q) => $q->whereHas('teachingAssignment.classRoom',
                fn ($c) => $c->where('school_id', $schoolId)))
            ->whereIn('status', SiklusUjian::statusTerlihat($user));

        // Guru hanya melihat ujian yang ia ampu — sama seperti daftar Ujian/CBT.
        if ($user->hasRole('Guru') && $user->teacher) {
            $ujian->whereHas('teachingAssignment', fn ($q) => $q->where('teacher_id', $user->teacher->id));
        }

        $idUjian = $ujian->pluck('id');

        // Satu query untuk seluruh cacah per status.
        $perStatus = Exam::whereIn('id', $idUjian)
            ->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        $idSesi = ExamSession::whereIn('exam_id', $idUjian)->pluck('id');

        $perPengerjaan = ExamAttempt::whereIn('exam_session_id', $idSesi)
            ->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        $soal = Question::whereIn('exam_id', $idUjian)
            ->selectRaw('type, count(*) as n')->groupBy('type')->pluck('n', 'type');

        $nilai = ExamAttempt::whereIn('exam_session_id', $idSesi)
            ->where('status', 'graded')
            ->selectRaw('count(*) as n, avg(final_score) as rata, min(final_score) as terendah, max(final_score) as tertinggi')
            ->first();

        // Lulus dibandingkan KKM masing-masing ujian, bukan satu angka tetap.
        $lulus = ExamAttempt::whereIn('exam_session_id', $idSesi)
            ->where('exam_attempts.status', 'graded')
            ->join('exam_sessions', 'exam_sessions.id', '=', 'exam_attempts.exam_session_id')
            ->join('exams', 'exams.id', '=', 'exam_sessions.exam_id')
            ->whereColumn('exam_attempts.final_score', '>=', 'exams.pass_score')
            ->count();

        $sekarang = now();

        return [
            'ujian_total' => $idUjian->count(),
            'ujian_per_status' => [
                SiklusUjian::DRAFT => (int) ($perStatus[SiklusUjian::DRAFT] ?? 0),
                SiklusUjian::TERSEDIA => (int) ($perStatus[SiklusUjian::TERSEDIA] ?? 0),
                SiklusUjian::SELESAI => (int) ($perStatus[SiklusUjian::SELESAI] ?? 0),
                SiklusUjian::RIWAYAT => (int) ($perStatus[SiklusUjian::RIWAYAT] ?? 0),
            ],
            'soal_pg' => (int) ($soal['mc'] ?? 0),
            'soal_essay' => (int) ($soal['essay'] ?? 0),
            'bank_soal' => QuestionBank::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count(),
            'siswa' => Student::when($schoolId, fn ($q) => $q->where('school_id', $schoolId))->count(),

            'jadwal_berjalan' => ExamSession::whereIn('id', $idSesi)
                ->where('starts_at', '<=', $sekarang)->where('ends_at', '>=', $sekarang)->count(),
            'jadwal_akan_datang' => ExamSession::whereIn('id', $idSesi)
                ->where('starts_at', '>', $sekarang)->count(),

            'sedang_mengerjakan' => (int) ($perPengerjaan['in_progress'] ?? 0),
            'menunggu_dinilai' => (int) ($perPengerjaan['submitted'] ?? 0),
            'sudah_dinilai' => (int) ($perPengerjaan['graded'] ?? 0),

            // Essay yang benar-benar menunggu guru: sudah dikumpulkan tapi belum dinilai.
            'essay_menunggu' => ExamAttempt::whereIn('exam_session_id', $idSesi)
                ->where('status', 'submitted')->where('essay_graded', false)->count(),

            'nilai_jumlah' => (int) ($nilai->n ?? 0),
            'nilai_rata' => $nilai && $nilai->n ? round((float) $nilai->rata, 2) : null,
            'nilai_terendah' => $nilai && $nilai->n ? round((float) $nilai->terendah, 2) : null,
            'nilai_tertinggi' => $nilai && $nilai->n ? round((float) $nilai->tertinggi, 2) : null,
            'lulus' => $lulus,
            'tidak_lulus' => max(0, (int) ($nilai->n ?? 0) - $lulus),

            // Daftar ujian yang jadwalnya sedang berjalan, dengan kemajuannya.
            'berjalan' => ExamSession::whereIn('id', $idSesi)
                ->with(['exam.teachingAssignment.subject', 'exam.teachingAssignment.classRoom'])
                ->where('starts_at', '<=', $sekarang)->where('ends_at', '>=', $sekarang)
                ->withCount([
                    'attempts as jml_peserta',
                    'attempts as jml_selesai' => fn ($q) => $q->whereIn('status', ['submitted', 'graded']),
                    'attempts as jml_berjalan' => fn ($q) => $q->where('status', 'in_progress'),
                ])
                ->orderBy('ends_at')
                ->limit(5)->get(),
        ];
    }

    /** Ringkasan untuk satu siswa. */
    public function untukSiswa(Student $siswa): array
    {
        $sekarang = now();

        // Sesi yang memang diberikan ke siswa ini: dilampirkan manual ATAU
        // lewat rombelnya. Dua jalur itu memang dipakai bergantian oleh guru.
        $idKelas = DB::table('class_students')->where('student_id', $siswa->id)->pluck('class_room_id');

        $sesi = ExamSession::query()
            ->whereHas('exam', fn ($q) => $q->where('status', SiklusUjian::TERSEDIA))
            ->where(fn ($q) => $q
                ->whereIn('class_room_id', $idKelas)
                ->orWhereHas('students', fn ($s) => $s->where('students.id', $siswa->id)));

        $idSesi = $sesi->pluck('id');

        $percobaan = ExamAttempt::where('student_id', $siswa->id)
            ->whereIn('exam_session_id', $idSesi)
            ->get(['id', 'exam_session_id', 'status', 'final_score', 'submitted_at']);

        $sudah = $percobaan->pluck('exam_session_id')->all();

        $dinilai = $percobaan->where('status', 'graded');

        return [
            'siap_dikerjakan' => ExamSession::whereIn('id', $idSesi)
                ->whereNotIn('id', $sudah)
                ->where('starts_at', '<=', $sekarang)->where('ends_at', '>=', $sekarang)
                ->with(['exam.teachingAssignment.subject'])->orderBy('ends_at')->get(),

            'sedang_dikerjakan' => ExamSession::whereIn('id', $percobaan->where('status', 'in_progress')->pluck('exam_session_id'))
                ->with(['exam.teachingAssignment.subject'])->get(),

            'akan_datang' => ExamSession::whereIn('id', $idSesi)
                ->where('starts_at', '>', $sekarang)
                ->with(['exam.teachingAssignment.subject'])->orderBy('starts_at')->limit(5)->get(),

            'menunggu_nilai' => $percobaan->where('status', 'submitted')->count(),
            'sudah_dinilai' => $dinilai->count(),
            'nilai_rata' => $dinilai->count() ? round((float) $dinilai->avg('final_score'), 2) : null,
            'nilai_tertinggi' => $dinilai->count() ? round((float) $dinilai->max('final_score'), 2) : null,

            'hasil_terakhir' => ExamAttempt::where('student_id', $siswa->id)
                ->where('status', 'graded')
                ->with(['session.exam.teachingAssignment.subject'])
                ->latest('submitted_at')->limit(5)->get(),

            // Data yang tercetak di kartu ujian — ditampilkan agar siswa tidak
            // perlu membuka kartunya hanya untuk melihat ruang/gelombang.
            'gelombang' => $siswa->wave->name ?? null,
            'ruang' => $siswa->room,
            'id_proktor' => $siswa->proctor_id,
        ];
    }
}
