<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ClassStudent;
use App\Models\ExamSession;
use App\Models\ExamAttempt;
use App\Models\ExamAnswer;
use App\Models\Notification;
use App\Services\CbtScoringService;
use App\Services\GamificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExamPortalController extends Controller
{
    /** Daftar ujian yang ditugaskan ke siswa. */
    public function index()
    {
        $student = auth()->user()->student;
        if (!$student) {
            return redirect()->route('student.dashboard')->with('error', 'Profil siswa tidak ditemukan.');
        }

        $classIds = ClassStudent::where('student_id', $student->id)->pluck('class_room_id');

        $sessions = ExamSession::with(['exam.teachingAssignment.subject', 'classRoom'])
            ->whereHas('exam', fn ($q) => $q->where('status', 'published'))
            ->where(function ($q) use ($classIds, $student) {
                $q->whereIn('class_room_id', $classIds)
                  ->orWhereHas('students', fn ($s) => $s->where('students.id', $student->id));
            })
            ->orderBy('starts_at', 'desc')
            ->get();

        $attempts = ExamAttempt::where('student_id', $student->id)
            ->whereIn('exam_session_id', $sessions->pluck('id'))
            ->get()->keyBy('exam_session_id');

        return view('frontend.exams.index', compact('sessions', 'attempts'));
    }

    /** Mulai mengerjakan: buat attempt bila valid. */
    public function start($sessionId)
    {
        $student = auth()->user()->student;
        $session = ExamSession::with('exam')->findOrFail($sessionId);

        if (!$this->isEligible($session, $student)) {
            return redirect()->route('student.exams.index')->with('error', 'Anda tidak terdaftar pada ujian ini.');
        }

        $existing = ExamAttempt::where('exam_session_id', $session->id)->where('student_id', $student->id)->first();
        if ($existing) {
            return redirect()->route('student.exams.attempt', $session->id);
        }

        if (!$session->isWithinSchedule()) {
            return redirect()->route('student.exams.index')->with('error', 'Ujian belum dibuka atau sudah ditutup.');
        }

        // Cek kuota.
        if ($session->max_capacity) {
            $count = ExamAttempt::where('exam_session_id', $session->id)->count();
            if ($count >= $session->max_capacity) {
                return redirect()->route('student.exams.index')->with('error', 'Kuota peserta sesi ini sudah penuh.');
            }
        }

        $now = Carbon::now();
        $endByDuration = $now->copy()->addMinutes($session->duration_minutes);
        $ends = $endByDuration->lt($session->ends_at) ? $endByDuration : $session->ends_at;

        ExamAttempt::create([
            'exam_session_id' => $session->id,
            'student_id' => $student->id,
            'started_at' => $now,
            'ends_at' => $ends,
            'status' => 'in_progress',
        ]);

        return redirect()->route('student.exams.attempt', $session->id);
    }

    /** Halaman pengerjaan. */
    public function attempt($sessionId)
    {
        $student = auth()->user()->student;
        $session = ExamSession::with('exam.questions.options')->findOrFail($sessionId);

        if (!$this->isEligible($session, $student)) {
            return redirect()->route('student.exams.index')->with('error', 'Anda tidak terdaftar pada ujian ini.');
        }

        $attempt = ExamAttempt::where('exam_session_id', $session->id)->where('student_id', $student->id)->first();
        if (!$attempt) {
            return redirect()->route('student.exams.index')->with('error', 'Silakan klik "Mulai" untuk memulai ujian.');
        }
        if ($attempt->status !== 'in_progress') {
            return redirect()->route('student.exams.result', $attempt->id);
        }

        // Waktu habis → auto submit.
        if (Carbon::now()->gte($attempt->ends_at)) {
            CbtScoringService::finalizeOnSubmit($attempt);
            $this->afterFinalize($attempt);
            return redirect()->route('student.exams.result', $attempt->id)->with('error', 'Waktu ujian telah habis, jawaban otomatis dikumpulkan.');
        }

        $exam = $session->exam;
        $questions = $exam->questions;
        if ($session->shuffle_questions) {
            $questions = $questions->shuffle(crc32($attempt->id));
        }

        $answers = $attempt->answers()->get()->keyBy('question_id');
        $remaining = max(0, Carbon::now()->diffInSeconds($attempt->ends_at, false));

        return view('frontend.exams.attempt', compact('session', 'exam', 'attempt', 'questions', 'answers', 'remaining'));
    }

    /** Autosave satu jawaban (AJAX JSON). */
    public function saveAnswer(Request $request)
    {
        $student = auth()->user()->student;
        $attempt = ExamAttempt::with('session')->findOrFail($request->attempt_id);

        if ($attempt->student_id !== $student->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }
        if ($attempt->status !== 'in_progress') {
            return response()->json(['message' => 'Ujian sudah dikumpulkan.'], 422);
        }
        if (Carbon::now()->gte($attempt->ends_at)) {
            return response()->json(['message' => 'Waktu habis.', 'expired' => true], 422);
        }

        $request->validate(['question_id' => 'required|uuid|exists:questions,id']);

        ExamAnswer::updateOrCreate(
            ['exam_attempt_id' => $attempt->id, 'question_id' => $request->question_id],
            [
                'selected_option_id' => $request->filled('selected_option_id') ? $request->selected_option_id : null,
                'answer_text' => $request->answer_text,
            ]
        );

        return response()->json(['message' => 'tersimpan']);
    }

    /** Kumpulkan ujian. */
    public function submit($sessionId)
    {
        $student = auth()->user()->student;
        $attempt = ExamAttempt::where('exam_session_id', $sessionId)
            ->where('student_id', $student->id)->firstOrFail();

        if ($attempt->status === 'in_progress') {
            CbtScoringService::finalizeOnSubmit($attempt);
            $this->afterFinalize($attempt);
        }

        return redirect()->route('student.exams.result', $attempt->id)->with('success', 'Ujian berhasil dikumpulkan.');
    }

    /** Halaman hasil. */
    public function result($attemptId)
    {
        $student = auth()->user()->student;
        $attempt = ExamAttempt::with(['session.exam', 'answers'])->findOrFail($attemptId);
        if ($attempt->student_id !== $student->id) {
            abort(403);
        }

        return view('frontend.exams.result', compact('attempt'));
    }

    /** Notifikasi + badge bila attempt langsung graded (PG-saja otomatis). */
    private function afterFinalize(ExamAttempt $attempt): void
    {
        $attempt->refresh();
        if ($attempt->status === 'graded') {
            GamificationService::evaluateExamScore($attempt);
            try {
                $userId = auth()->id();
                Notification::create([
                    'user_id' => $userId,
                    'title' => 'Nilai Ujian Keluar 🎯',
                    'message' => 'Nilai ujian "' . ($attempt->session->exam->title ?? '') . '": ' . rtrim(rtrim((string) $attempt->final_score, '0'), '.') . '.',
                    'type' => 'exam',
                    'url' => route('student.exams.index'),
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Notif hasil ujian gagal: ' . $e->getMessage());
            }
        }
    }

    private function isEligible(ExamSession $session, $student): bool
    {
        if (!$student) {
            return false;
        }
        if ($session->class_room_id) {
            $inClass = ClassStudent::where('student_id', $student->id)
                ->where('class_room_id', $session->class_room_id)->exists();
            if ($inClass) {
                return true;
            }
        }
        return $session->students()->where('students.id', $student->id)->exists();
    }
}
