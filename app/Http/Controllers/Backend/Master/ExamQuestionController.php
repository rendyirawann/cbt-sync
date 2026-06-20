<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ExamQuestionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|uuid|exists:exams,id',
            'type' => 'required|in:mc,essay',
            'question_text' => 'required|string',
            'points' => 'required|numeric|min:0',
            'penalty' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $exam = Exam::findOrFail($request->exam_id);
        $this->authorizeExam($exam);

        if ($request->type === 'mc') {
            $request->validate([
                'options' => 'required|array|min:2',
                'correct' => 'required',
            ]);
        }

        try {
            DB::transaction(function () use ($request, $exam) {
                $data = [
                    'exam_id' => $exam->id,
                    'type' => $request->type,
                    'question_text' => $request->question_text,
                    'points' => $request->points,
                    'penalty' => $request->penalty ?: 0,
                    'order' => ($exam->questions()->max('order') ?? 0) + 1,
                ];

                if ($request->hasFile('image')) {
                    $data['image_path'] = $request->file('image')->store('exam-questions', 'public');
                }

                $question = Question::create($data);

                if ($request->type === 'mc') {
                    $this->syncOptions($question, $request->options, (int) $request->correct);
                }
            });

            return redirect()->back()->with('success', 'Soal berhasil ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambah soal: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $question = Question::with('exam')->findOrFail($id);
        $this->authorizeExam($question->exam);

        $request->validate([
            'question_text' => 'required|string',
            'points' => 'required|numeric|min:0',
            'penalty' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($question->type === 'mc') {
            $request->validate([
                'options' => 'required|array|min:2',
                'correct' => 'required',
            ]);
        }

        try {
            DB::transaction(function () use ($request, $question) {
                $data = [
                    'question_text' => $request->question_text,
                    'points' => $request->points,
                    'penalty' => $request->penalty ?: 0,
                ];

                if ($request->hasFile('image')) {
                    if ($question->image_path) {
                        Storage::disk('public')->delete($question->image_path);
                    }
                    $data['image_path'] = $request->file('image')->store('exam-questions', 'public');
                }

                $question->update($data);

                if ($question->type === 'mc') {
                    $question->options()->delete();
                    $this->syncOptions($question, $request->options, (int) $request->correct);
                }
            });

            return redirect()->back()->with('success', 'Soal berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui soal: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $question = Question::with('exam')->findOrFail($id);
        $this->authorizeExam($question->exam);

        if ($question->image_path) {
            Storage::disk('public')->delete($question->image_path);
        }
        $question->delete();

        return redirect()->back()->with('success', 'Soal berhasil dihapus.');
    }

    /** Buat opsi PG dengan label A, B, C, ... dan tandai kunci jawaban. */
    private function syncOptions(Question $question, array $options, int $correctIndex): void
    {
        $i = 0;
        foreach (array_values($options) as $idx => $text) {
            if (trim((string) $text) === '') {
                continue;
            }
            $question->options()->create([
                'label' => chr(65 + $i), // A, B, C ...
                'option_text' => $text,
                'is_correct' => ($idx === $correctIndex),
                'order' => $i,
            ]);
            $i++;
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
