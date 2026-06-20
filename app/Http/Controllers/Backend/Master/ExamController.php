<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = Exam::with(['teachingAssignment.subject', 'teachingAssignment.classRoom', 'teachingAssignment.teacher.user'])
            ->withCount(['questions', 'sessions']);

        if ($user->hasRole('Guru')) {
            if (!$user->teacher) {
                return redirect()->route('dashboard')->with('error', 'Profil guru tidak ditemukan.');
            }
            $teacherId = $user->teacher->id;
            $query->whereHas('teachingAssignment', fn ($q) => $q->where('teacher_id', $teacherId));
            $assignments = TeachingAssignment::with(['subject', 'classRoom'])
                ->where('teacher_id', $teacherId)->get();
        } else {
            $assignments = TeachingAssignment::with(['subject', 'classRoom', 'teacher.user'])->get();
        }

        $exams = $query->latest()->get();

        return view('backend.master.exams.index', compact('exams', 'assignments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'teaching_assignment_id' => 'required|uuid|exists:teaching_assignments,id',
            'title' => 'required|string|max:255',
            'type' => 'required|in:mixed,mc,essay',
            'points_mode' => 'required|in:per_question,equal,manual',
            'wrong_penalty' => 'nullable|numeric|min:0',
            'pass_score' => 'nullable|numeric|min:0|max:100',
        ]);

        $exam = Exam::create([
            'teaching_assignment_id' => $request->teaching_assignment_id,
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'points_mode' => $request->points_mode,
            'wrong_penalty' => $request->wrong_penalty ?: 0,
            'normalize' => $request->has('normalize'),
            'pass_score' => $request->pass_score ?: 75,
            'status' => 'draft',
        ]);

        return redirect()->route('exams.show', $exam->id)
            ->with('success', 'Ujian berhasil dibuat. Silakan tambahkan soal.');
    }

    public function show($id)
    {
        $exam = Exam::with([
            'teachingAssignment.subject', 'teachingAssignment.classRoom', 'teachingAssignment.teacher.user',
            'questions.options',
            'sessions.classRoom', 'sessions.attempts',
        ])->findOrFail($id);

        $this->authorizeExam($exam);

        // Untuk pembuatan sesi: kelas yang tersedia + siswa (untuk daftar manual).
        $classRooms = \App\Models\ClassRoom::orderBy('name')->get();
        $students = \App\Models\Student::with('user')->get();

        return view('backend.master.exams.show', compact('exam', 'classRooms', 'students'));
    }

    public function update(Request $request, $id)
    {
        $exam = Exam::findOrFail($id);
        $this->authorizeExam($exam);

        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:mixed,mc,essay',
            'points_mode' => 'required|in:per_question,equal,manual',
            'wrong_penalty' => 'nullable|numeric|min:0',
            'pass_score' => 'nullable|numeric|min:0|max:100',
        ]);

        $exam->update([
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'points_mode' => $request->points_mode,
            'wrong_penalty' => $request->wrong_penalty ?: 0,
            'normalize' => $request->has('normalize'),
            'pass_score' => $request->pass_score ?: 75,
        ]);

        return redirect()->back()->with('success', 'Pengaturan ujian berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $exam = Exam::findOrFail($id);
        $this->authorizeExam($exam);
        $exam->delete();

        return redirect()->route('exams.index')->with('success', 'Ujian berhasil dihapus.');
    }

    public function publish($id)
    {
        $exam = Exam::with('questions')->findOrFail($id);
        $this->authorizeExam($exam);

        if ($exam->status === 'draft' && $exam->questions->count() === 0) {
            return redirect()->back()->with('error', 'Tidak bisa menerbitkan ujian tanpa soal.');
        }

        $exam->update(['status' => $exam->status === 'draft' ? 'published' : 'draft']);

        return redirect()->back()->with('success',
            $exam->status === 'published' ? 'Ujian diterbitkan.' : 'Ujian dikembalikan ke draft.');
    }

    /** Guru hanya boleh mengelola ujian miliknya. */
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
