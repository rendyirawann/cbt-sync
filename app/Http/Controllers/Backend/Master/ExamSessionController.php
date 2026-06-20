<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamSession;
use App\Models\Notification;
use Illuminate\Http\Request;

class ExamSessionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|uuid|exists:exams,id',
            'name' => 'required|string|max:255',
            'participant_mode' => 'required|in:class,manual',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'duration_minutes' => 'required|integer|min:1',
            'max_capacity' => 'nullable|integer|min:1',
        ]);

        $exam = Exam::findOrFail($request->exam_id);
        $this->authorizeExam($exam);

        if ($request->participant_mode === 'class') {
            $request->validate(['class_room_id' => 'required|uuid|exists:class_rooms,id']);
        } else {
            $request->validate(['students' => 'required|array|min:1']);
        }

        $session = ExamSession::create([
            'exam_id' => $exam->id,
            'name' => $request->name,
            'class_room_id' => $request->participant_mode === 'class' ? $request->class_room_id : null,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
            'duration_minutes' => $request->duration_minutes,
            'max_capacity' => $request->max_capacity,
            'shuffle_questions' => $request->has('shuffle_questions'),
            'shuffle_options' => $request->has('shuffle_options'),
            'show_result' => $request->has('show_result'),
            'status' => 'scheduled',
        ]);

        if ($request->participant_mode === 'manual') {
            $session->students()->sync($request->students);
        }

        // Notifikasi ke peserta (jika ujian sudah terbit).
        if ($exam->status === 'published') {
            $this->notifyParticipants(
                $session,
                'Ujian Dijadwalkan: ' . $exam->title . ' 🖥️',
                'Sesi "' . $session->name . '" dijadwalkan ' . \Carbon\Carbon::parse($session->starts_at)->format('d M Y H:i') . '. Durasi ' . $session->duration_minutes . ' menit.'
            );
        }

        return redirect()->route('exams.show', $exam->id)->with('success', 'Sesi ujian berhasil dibuat.');
    }

    public function update(Request $request, $id)
    {
        $session = ExamSession::with('exam')->findOrFail($id);
        $this->authorizeExam($session->exam);

        $request->validate([
            'name' => 'required|string|max:255',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'duration_minutes' => 'required|integer|min:1',
            'max_capacity' => 'nullable|integer|min:1',
        ]);

        $session->update($request->only(['name', 'starts_at', 'ends_at', 'duration_minutes', 'max_capacity'])
            + [
                'shuffle_questions' => $request->has('shuffle_questions'),
                'shuffle_options' => $request->has('shuffle_options'),
                'show_result' => $request->has('show_result'),
            ]);

        return redirect()->back()->with('success', 'Sesi diperbarui.');
    }

    public function destroy($id)
    {
        $session = ExamSession::with('exam')->findOrFail($id);
        $this->authorizeExam($session->exam);
        $examId = $session->exam_id;
        $session->delete();

        return redirect()->route('exams.show', $examId)->with('success', 'Sesi dihapus.');
    }

    private function notifyParticipants(ExamSession $session, string $title, string $message): void
    {
        try {
            foreach ($session->eligibleStudents() as $student) {
                if (!$student->user_id) {
                    continue;
                }
                Notification::create([
                    'user_id' => $student->user_id,
                    'title' => $title,
                    'message' => $message,
                    'type' => 'exam',
                    'url' => route('student.exams.index'),
                ]);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal kirim notifikasi ujian: ' . $e->getMessage());
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
