<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExamController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = Exam::with(['teachingAssignment.subject', 'teachingAssignment.classRoom', 'teachingAssignment.teacher.user'])
            ->withCount(['questions', 'sessions']);

        $sid = \App\Support\SchoolScope::id();

        if ($user->hasRole('Guru')) {
            if (!$user->teacher) {
                return redirect()->route('dashboard')->with('error', 'Profil guru tidak ditemukan.');
            }
            $teacherId = $user->teacher->id;
            $query->whereHas('teachingAssignment', fn ($q) => $q->where('teacher_id', $teacherId));
            $assignments = TeachingAssignment::with(['subject', 'classRoom'])
                ->where('teacher_id', $teacherId)->get();
        } else {
            // Kepala Sekolah / user yang discope → hanya ujian di sekolahnya (via kelas).
            if ($sid) {
                $query->whereHas('teachingAssignment.classRoom', fn ($c) => $c->where('school_id', $sid));
            }
            $assignments = TeachingAssignment::with(['subject', 'classRoom', 'teacher.user'])
                ->when($sid, fn ($q) => $q->whereHas('classRoom', fn ($c) => $c->where('school_id', $sid)))
                ->get();
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
            'sessions.classRoom', 'sessions.attempts', 'sessions.students',
        ])->findOrFail($id);

        $this->authorizeExam($exam);

        // Sesi ujian mengikuti kelas ujian (dari penugasan). Peserta = siswa kelas tsb;
        // "Pilih Siswa" hanya untuk memilih SEBAGIAN siswa kelas yang sama.
        $examClass = $exam->teachingAssignment?->classRoom;
        $classStudentIds = $examClass
            ? \App\Models\ClassStudent::where('class_room_id', $examClass->id)->pluck('student_id')
            : collect();
        $students = \App\Models\Student::with('user')->whereIn('id', $classStudentIds)->get();

        // Penugasan untuk mengganti kelas/mapel ujian (Guru: hanya miliknya).
        $user = auth()->user();
        $taQuery = TeachingAssignment::with(['subject', 'classRoom', 'teacher.user']);
        if ($user->hasRole('Guru')) {
            $taQuery->where('teacher_id', $user->teacher?->id);
        }
        $assignments = $taQuery->get();

        return view('backend.master.exams.show', compact('exam', 'examClass', 'students', 'assignments'));
    }

    public function update(Request $request, $id)
    {
        $exam = Exam::with('sessions.students')->findOrFail($id);
        $this->authorizeExam($exam);

        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:mixed,mc,essay',
            'points_mode' => 'required|in:per_question,equal,manual',
            'wrong_penalty' => 'nullable|numeric|min:0',
            'pass_score' => 'nullable|numeric|min:0|max:100',
            'teaching_assignment_id' => 'nullable|uuid|exists:teaching_assignments,id',
        ]);

        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'points_mode' => $request->points_mode,
            'wrong_penalty' => $request->wrong_penalty ?: 0,
            'normalize' => $request->has('normalize'),
            'pass_score' => $request->pass_score ?: 75,
        ];

        // Ganti kategori ujian: soal yang tak lagi sesuai tipe baru ikut terhapus
        // (mis. jadi "PG saja" → soal essay dihapus). Hanya selama belum ada yang memulai.
        if (!$exam->hasStartedAttempts() && $request->type !== $exam->type) {
            $this->pruneQuestionsForType($exam, $request->type);
        }

        // Kelas/mapel (penugasan) hanya boleh diganti selama belum ada yang memulai.
        if (!$exam->hasStartedAttempts() && $request->filled('teaching_assignment_id')
            && $request->teaching_assignment_id !== $exam->teaching_assignment_id) {
            $ta = TeachingAssignment::find($request->teaching_assignment_id);
            $user = auth()->user();
            if ($ta && $user->hasRole('Guru') && $ta->teacher_id !== $user->teacher?->id) {
                abort(403, 'Anda hanya dapat memilih penugasan milik Anda.');
            }
            if ($ta) {
                $oldClass = $exam->teachingAssignment?->class_room_id;
                $data['teaching_assignment_id'] = $ta->id;
                $this->moveSessionsToClass($exam, $oldClass, $ta->class_room_id);
            }
        }

        $exam->update($data);

        return redirect()->back()->with('success', 'Pengaturan ujian berhasil diperbarui.');
    }

    /**
     * Hapus soal yang tidak sesuai kategori ujian yang baru dipilih
     * (mixed→mc: hapus essay; mixed/mc→essay: hapus PG), termasuk file gambarnya.
     */
    private function pruneQuestionsForType(Exam $exam, string $newType): void
    {
        $remove = match ($newType) {
            'mc' => 'essay',
            'essay' => 'mc',
            default => null, // 'mixed' → simpan semua
        };
        if (!$remove) {
            return;
        }

        foreach ($exam->questions()->where('type', $remove)->with('options')->get() as $q) {
            if ($q->image_path) {
                Storage::disk('public')->delete($q->image_path);
            }
            foreach ($q->options as $opt) {
                if ($opt->image_path) {
                    Storage::disk('public')->delete($opt->image_path);
                }
            }
            $q->delete(); // opsi ikut terhapus (cascade)
        }
    }

    /** Saat kelas ujian berpindah, sesi ikut menyesuaikan kelas barunya. */
    private function moveSessionsToClass(Exam $exam, ?string $oldClass, ?string $newClass): void
    {
        if ($oldClass === $newClass || !$newClass) {
            return;
        }
        $validStudentIds = \App\Models\ClassStudent::where('class_room_id', $newClass)->pluck('student_id')->all();
        foreach ($exam->sessions as $sess) {
            if ($sess->class_room_id) {
                // Sesi mode "Satu Kelas" → pindah ke kelas baru.
                $sess->update(['class_room_id' => $newClass]);
            } else {
                // Sesi mode "Pilih Siswa" → sisakan hanya siswa yang ada di kelas baru.
                $keep = array_values(array_intersect($sess->students->pluck('id')->all(), $validStudentIds));
                $sess->students()->sync($keep);
            }
        }
    }

    public function destroy($id)
    {
        $exam = Exam::findOrFail($id);
        $this->authorizeExam($exam);

        if ($exam->hasStartedAttempts()) {
            return redirect()->back()->with('error', 'Ujian tidak bisa dihapus karena sudah ada siswa yang memulai/mengerjakan.');
        }

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

        // Sudah ada peserta yang memulai → ujian terkunci, tidak boleh ditarik ke draft.
        if ($exam->status === 'published' && $exam->hasStartedAttempts()) {
            return redirect()->back()->with('error', 'Ujian tidak bisa ditarik ke draft karena sudah ada siswa yang memulai ujian.');
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
