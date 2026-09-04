<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\ExamSession;
use App\Support\SchoolScope;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Pemantauan ujian yang sedang berlangsung.
 *
 * Guru hanya melihat sesi dari ujian yang ia ampu sendiri; Admin/Superadmin
 * melihat seluruh sesi di sekolahnya (SchoolScope), dan Developer melihat semua.
 * Halaman menyegarkan dirinya lewat endpoint JSON (polling) — tanpa websocket,
 * supaya tidak menambah service yang harus terus hidup di server.
 */
class ExamMonitorController extends Controller
{
    /** Daftar sesi yang bisa dipantau, sesi berjalan didahulukan. */
    public function index(Request $request)
    {
        $sessions = $this->sesiTerjangkau()
            ->with(['exam.teachingAssignment.subject', 'exam.teachingAssignment.teacher.user', 'classRoom'])
            ->orderByDesc('starts_at')
            ->limit(100)
            ->get();

        $now = Carbon::now();
        $sessions = $sessions->sortByDesc(fn ($s) => $s->isWithinSchedule($now) ? 1 : 0)->values();

        return view('backend.master.exams.monitor-index', compact('sessions'));
    }

    /** Halaman pemantauan satu sesi. */
    public function show($sessionId)
    {
        $session = $this->cariSesi($sessionId);

        return view('backend.master.exams.monitor', [
            'session' => $session,
            'ringkas' => $this->ringkas($session),
        ]);
    }

    /** Data status peserta untuk penyegaran berkala. */
    public function data($sessionId)
    {
        $session = $this->cariSesi($sessionId);

        return response()->json($this->ringkas($session));
    }

    /**
     * Status tiap peserta + hitungan per status.
     *
     * belum   = belum ada attempt (belum konfirmasi/tanda tangan)
     * kerja   = sedang mengerjakan
     * kunci   = terkunci (keluar layar, menunggu PIN pengawas)
     * selesai = sudah mengumpulkan / sudah dinilai
     */
    private function ringkas(ExamSession $session): array
    {
        $session->load(['attempts.student.user']);
        $attempts = $session->attempts->keyBy('student_id');
        $now = Carbon::now();

        $baris = $session->eligibleStudents()
            ->sortBy(fn ($s) => $s->user->name ?? '')
            ->map(function ($s) use ($attempts, $now) {
                $a = $attempts->get($s->id);

                if (!$a) {
                    $status = 'belum';
                } elseif (in_array($a->status, ['submitted', 'graded'], true)) {
                    $status = 'selesai';
                } elseif ($a->locked_at) {
                    $status = 'kunci';
                } else {
                    $status = 'kerja';
                }

                $sisa = null;
                if ($status === 'kerja' && $a->ends_at) {
                    $sisa = (int) max(0, round($now->diffInSeconds(Carbon::parse($a->ends_at), false)));
                }

                return [
                    'nama' => $s->user->name ?? '-',
                    'nisn' => $s->nisn ?: '-',
                    'status' => $status,
                    'mulai' => $a?->started_at ? Carbon::parse($a->started_at)->format('H:i') : null,
                    'kumpul' => $a?->submitted_at ? Carbon::parse($a->submitted_at)->format('H:i') : null,
                    'sisa_detik' => $sisa,
                    'keluar' => (int) ($a->leave_count ?? 0),
                    'kunci' => (int) ($a->lock_count ?? 0),
                    'nilai' => $a && $a->status === 'graded' ? (float) $a->final_score : null,
                    'ttd' => $a?->signature_path ? asset('storage/' . $a->signature_path) : null,
                    'attempt_id' => $a?->id,
                ];
            })->values();

        return [
            'diperbarui' => $now->format('H:i:s'),
            'jumlah' => [
                'total' => $baris->count(),
                'belum' => $baris->where('status', 'belum')->count(),
                'kerja' => $baris->where('status', 'kerja')->count(),
                'kunci' => $baris->where('status', 'kunci')->count(),
                'selesai' => $baris->where('status', 'selesai')->count(),
            ],
            'peserta' => $baris,
        ];
    }

    /** Kueri sesi sesuai hak akses peran yang sedang login. */
    private function sesiTerjangkau()
    {
        $user = auth()->user();
        $q = ExamSession::query();

        // Guru: hanya sesi dari ujian yang ia ampu.
        if ($user->hasRole('Guru')) {
            $teacherId = $user->teacher?->id;
            return $q->whereHas('exam.teachingAssignment', fn ($x) => $x->where('teacher_id', $teacherId));
        }

        // Admin/Superadmin: seluruh sesi di sekolahnya. Developer tidak discope.
        $sid = SchoolScope::id();

        return $q->when($sid, fn ($x) => $x->whereHas(
            'exam.teachingAssignment.classRoom',
            fn ($c) => $c->where('school_id', $sid)
        ));
    }

    /** Ambil satu sesi dengan penjagaan hak akses yang sama. */
    private function cariSesi($sessionId): ExamSession
    {
        return $this->sesiTerjangkau()
            ->with(['exam.teachingAssignment.subject', 'exam.teachingAssignment.teacher.user', 'classRoom'])
            ->findOrFail($sessionId);
    }
}
