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
            'is_active' => true,
            'resume_pin' => $this->generatePin(),
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

        if ($session->hasStartedAttempts()) {
            return redirect()->back()->with('error', 'Sesi tidak bisa diubah karena sudah ada peserta yang memulai.');
        }
        if ($session->isFinished()) {
            return redirect()->back()->with('error', 'Sesi tidak bisa diubah karena jadwalnya sudah terlewat/berakhir.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'participant_mode' => 'required|in:class,manual',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'duration_minutes' => 'required|integer|min:1',
            'max_capacity' => 'nullable|integer|min:1',
        ]);

        // Peserta (kelas / daftar siswa) hanya boleh diubah selama belum ada yang memulai —
        // dijamin oleh guard hasStartedAttempts di atas.
        if ($request->participant_mode === 'class') {
            $request->validate(['class_room_id' => 'required|uuid|exists:class_rooms,id']);
        } else {
            $request->validate(['students' => 'required|array|min:1']);
        }

        $session->update($request->only(['name', 'starts_at', 'ends_at', 'duration_minutes', 'max_capacity'])
            + [
                'class_room_id' => $request->participant_mode === 'class' ? $request->class_room_id : null,
                'shuffle_questions' => $request->has('shuffle_questions'),
                'shuffle_options' => $request->has('shuffle_options'),
                'show_result' => $request->has('show_result'),
            ]);

        // Sinkronkan daftar siswa manual (kosongkan bila memakai mode kelas).
        $session->students()->sync($request->participant_mode === 'manual' ? $request->students : []);

        return redirect()->back()->with('success', 'Sesi diperbarui.');
    }

    public function destroy($id)
    {
        $session = ExamSession::with('exam')->findOrFail($id);
        $this->authorizeExam($session->exam);

        if ($session->hasStartedAttempts()) {
            return redirect()->back()->with('error', 'Sesi tidak bisa dihapus karena sudah ada peserta yang memulai ujian.');
        }

        $examId = $session->exam_id;
        $session->delete();

        return redirect()->route('exams.show', $examId)->with('success', 'Sesi dihapus.');
    }

    /** Aktif/Nonaktifkan sesi — hanya boleh selama belum ada peserta yang memulai. */
    public function toggleActive($id)
    {
        $session = ExamSession::with('exam')->findOrFail($id);
        $this->authorizeExam($session->exam);

        if ($session->hasStartedAttempts()) {
            return redirect()->back()->with('error', 'Status sesi tidak bisa diubah karena sudah ada peserta yang memulai.');
        }

        $session->update(['is_active' => !$session->is_active]);

        return redirect()->back()->with('success',
            $session->is_active ? 'Sesi diaktifkan.' : 'Sesi dinonaktifkan (tersembunyi dari siswa).');
    }

    /** Buat ulang PIN pembuka kunci sesi. Boleh kapan saja (termasuk saat ujian berlangsung,
     *  karena guru butuh PIN untuk membuka kunci siswa yang keluar layar). */
    public function regeneratePin($id)
    {
        $session = ExamSession::with('exam')->findOrFail($id);
        $this->authorizeExam($session->exam);

        $session->update(['resume_pin' => $this->generatePin()]);

        return redirect()->back()->with('success', 'PIN sesi "' . $session->name . '" diperbarui: ' . $session->resume_pin);
    }

    /** PIN 6 digit acak. */
    private function generatePin(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
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
