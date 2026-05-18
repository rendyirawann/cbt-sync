<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssignmentController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = Assignment::with(['teachingAssignment.teacher.user', 'teachingAssignment.subject', 'teachingAssignment.classRoom']);

        if ($user->hasRole('Guru')) {
            $teacherId = $user->teacher->id;
            $query->whereHas('teachingAssignment', function($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId);
            });
            $assignments = TeachingAssignment::with(['subject', 'classRoom'])
                ->where('teacher_id', $teacherId)
                ->get();
        } elseif ($user->hasRole('Siswa')) {
            if (!$user->student) {
                return redirect()->route('student.dashboard')->with('error', 'Profil siswa tidak ditemukan.');
            }
            
            $studentId = $user->student->id;
            $classId = \App\Models\ClassStudent::where('student_id', $studentId)
                ->whereHas('academicYear', function($q) { $q->where('is_active', 1); })
                ->value('class_room_id');

            $query->whereHas('teachingAssignment', function($q) use ($classId) {
                $q->where('class_room_id', $classId);
            });
            
            $assignments = [];
        } else {
            $assignments = TeachingAssignment::with(['teacher.user', 'subject', 'classRoom'])->get();
        }

        $items = $query->latest()->get();
        return view('backend.master.assignments.index', compact('items', 'assignments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'teaching_assignment_id' => 'required',
            'title' => 'required|string|max:255',
            'due_date' => 'required|date',
            'file' => 'nullable|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx|max:20480',
        ]);

        try {
            $data = [
                'teaching_assignment_id' => $request->teaching_assignment_id,
                'title' => $request->title,
                'description' => $request->description,
                'due_date' => $request->due_date,
            ];

            if ($request->hasFile('file')) {
                $data['file_path'] = $request->file('file')->store('assignments', 'public');
            }

            $assignment = Assignment::create($data);

            \App\Services\NotificationService::sendAssignmentNotification($assignment);

            return redirect()->back()->with('success', 'Penugasan berhasil dibuat!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuat penugasan: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $item = Assignment::findOrFail($id);
        
        $request->validate([
            'title' => 'required|string|max:255',
            'due_date' => 'required|date',
        ]);

        try {
            $data = [
                'title' => $request->title,
                'description' => $request->description,
                'due_date' => $request->due_date,
            ];

            if ($request->hasFile('file')) {
                if ($item->file_path) {
                    Storage::disk('public')->delete($item->file_path);
                }
                $data['file_path'] = $request->file('file')->store('assignments', 'public');
            }

            $item->update($data);

            return redirect()->back()->with('success', 'Penugasan berhasil diperbarui!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui penugasan: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $item = Assignment::findOrFail($id);
            if ($item->file_path) {
                Storage::disk('public')->delete($item->file_path);
            }
            $item->delete();

            return redirect()->back()->with('success', 'Penugasan berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menghapus penugasan: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $assignment = Assignment::with(['teachingAssignment.classRoom', 'teachingAssignment.subject', 'submissions.student.user'])->findOrFail($id);
        
        // Ambil semua siswa di kelas tersebut
        $students = \App\Models\ClassStudent::where('class_room_id', $assignment->teachingAssignment->class_room_id)
            ->with('student.user')
            ->get();

        return view('backend.master.assignments.show', compact('assignment', 'students'));
    }

    public function score(Request $request, $submissionId)
    {
        $request->validate([
            'score' => 'required|integer|min:0|max:100',
            'feedback' => 'nullable|string'
        ]);

        try {
            $submission = \App\Models\AssignmentSubmission::findOrFail($submissionId);
            $submission->update([
                'score' => $request->score,
                'feedback' => $request->feedback
            ]);

            // Award badges if applicable (e.g. perfect score badge)
            \App\Services\GamificationService::evaluateScore($submission);

            return redirect()->back()->with('success', 'Nilai berhasil disimpan!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan nilai: ' . $e->getMessage());
        }
    }

    public function submit(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|mimes:pdf,zip,rar,doc,docx|max:10240', // Max 10MB
        ]);

        try {
            $studentId = auth()->user()->student->id;
            
            $path = $request->file('file')->store('submissions', 'public');

            $submission = \App\Models\AssignmentSubmission::updateOrCreate(
                [
                    'assignment_id' => $id,
                    'student_id' => $studentId,
                ],
                [
                    'file_path' => $path,
                    'student_note' => $request->student_note,
                    'submitted_at' => now(),
                ]
            );

            // Award badges if applicable (quick submission, early bird, diligent student)
            \App\Services\GamificationService::evaluateSubmission($submission);

            return redirect()->back()->with('success', 'Tugas berhasil dikirim!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengirim tugas: ' . $e->getMessage());
        }
    }
}
