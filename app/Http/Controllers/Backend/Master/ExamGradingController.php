<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAnswer;
use App\Models\ExamSession;
use App\Models\Notification;
use App\Services\CbtScoringService;
use App\Services\GamificationService;
use Illuminate\Http\Request;

class ExamGradingController extends Controller
{
    /** Daftar peserta sebuah sesi + status pengerjaan/nilai. */
    public function attempts($sessionId)
    {
        $session = ExamSession::with([
            'exam.teachingAssignment.subject', 'exam.teachingAssignment.classRoom',
            'classRoom', 'attempts.student.user',
        ])->findOrFail($sessionId);

        $this->authorizeExam($session->exam);

        $eligible = $session->eligibleStudents();
        $attemptsByStudent = $session->attempts->keyBy('student_id');

        return view('backend.master.exams.attempts', compact('session', 'eligible', 'attemptsByStudent'));
    }

    /** Halaman periksa satu attempt (koreksi essay + lihat hasil PG). */
    public function grade($attemptId)
    {
        $attempt = ExamAttempt::with([
            'session.exam.questions.options', 'student.user', 'answers',
        ])->findOrFail($attemptId);

        $this->authorizeExam($attempt->session->exam);

        $exam = $attempt->session->exam;
        $count = $exam->questions->count();
        $answers = $attempt->answers->keyBy('question_id');

        // Bobot (skor maks) tiap soal sesuai mode penilaian.
        $weights = [];
        foreach ($exam->questions as $q) {
            $weights[$q->id] = CbtScoringService::questionWeight($exam, $q, $count);
        }

        return view('backend.master.exams.grade', compact('attempt', 'exam', 'answers', 'weights'));
    }

    /** Simpan nilai essay (+ nilai akhir manual), lalu akumulasi & notifikasi. */
    public function storeGrade(Request $request, $attemptId)
    {
        $attempt = ExamAttempt::with(['session.exam.questions', 'answers'])->findOrFail($attemptId);
        $this->authorizeExam($attempt->session->exam);

        $exam = $attempt->session->exam;
        $count = $exam->questions->count();
        $scores = $request->input('scores', []);     // [question_id => nilai]
        $feedbacks = $request->input('feedback', []); // [question_id => catatan]

        foreach ($exam->questions->where('type', 'essay') as $q) {
            $weight = CbtScoringService::questionWeight($exam, $q, $count);
            $val = isset($scores[$q->id]) ? (float) $scores[$q->id] : 0;
            $val = max(0, min($val, $weight)); // clamp 0..maks

            ExamAnswer::updateOrCreate(
                ['exam_attempt_id' => $attempt->id, 'question_id' => $q->id],
                ['earned_score' => round($val, 2), 'feedback' => $feedbacks[$q->id] ?? null, 'graded' => true]
            );
        }

        if ($exam->points_mode === 'manual') {
            $request->validate(['final_score' => 'required|numeric|min:0']);
            $essayScore = (float) $attempt->answers()
                ->whereHas('question', fn ($x) => $x->where('type', 'essay'))->sum('earned_score');
            $attempt->update([
                'essay_score' => round($essayScore, 2),
                'total_score' => round((float) $attempt->mc_score + $essayScore, 2),
                'final_score' => round((float) $request->final_score, 2),
                'essay_graded' => true,
                'status' => 'graded',
            ]);
        } else {
            CbtScoringService::recomputeAfterEssayGrading($attempt);
        }

        $attempt->refresh();
        $this->notifyResult($attempt);
        GamificationService::evaluateExamScore($attempt);

        return redirect()->route('exam-sessions.attempts', $attempt->exam_session_id)
            ->with('success', 'Nilai berhasil disimpan. Nilai akhir: ' . rtrim(rtrim((string) $attempt->final_score, '0'), '.'));
    }

    private function notifyResult(ExamAttempt $attempt): void
    {
        try {
            $userId = $attempt->student->user_id ?? null;
            if (!$userId) {
                return;
            }
            Notification::create([
                'user_id' => $userId,
                'title' => 'Nilai Ujian Keluar 🎯',
                'message' => 'Nilai ujian "' . ($attempt->session->exam->title ?? '') . '" sudah keluar: ' . rtrim(rtrim((string) $attempt->final_score, '0'), '.') . '.',
                'type' => 'exam',
                'url' => route('student.exams.index'),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal notif nilai ujian: ' . $e->getMessage());
        }
    }

    private function authorizeExam(Exam $exam): void
    {
        $user = auth()->user();
        if ($user->hasRole('Guru')) {
            $teacherId = $user->teacher?->id;
            if (!$teacherId || $exam->teachingAssignment?->teacher_id !== $teacherId) {
                abort(403, 'Anda hanya dapat mengelola ujian milik Anda.');
            }
        }
    }
}
