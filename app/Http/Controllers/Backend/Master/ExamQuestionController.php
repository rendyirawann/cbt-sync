<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionOption;
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:3072',
            'option_images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:3072',
        ], $this->imageMessages());

        $exam = Exam::findOrFail($request->exam_id);
        $this->authorizeExam($exam);

        if ($exam->hasStartedAttempts()) {
            return redirect()->back()->with('error', 'Soal tidak bisa ditambah karena sudah ada siswa yang memulai ujian.');
        }

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
                    $this->syncOptions($question, $request);
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

        if ($question->exam->hasStartedAttempts()) {
            return redirect()->back()->with('error', 'Soal tidak bisa diedit karena sudah ada siswa yang memulai ujian.');
        }

        $request->validate([
            'question_text' => 'required|string',
            'points' => 'required|numeric|min:0',
            'penalty' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:3072',
            'option_images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:3072',
        ], $this->imageMessages());

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
                    $this->syncOptionsUpdate($question, $request);
                }
            });

            return redirect()->back()->with('success', 'Soal berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui soal: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $question = Question::with('exam', 'options')->findOrFail($id);
        $this->authorizeExam($question->exam);

        if ($question->exam->hasStartedAttempts()) {
            return redirect()->back()->with('error', 'Soal tidak bisa dihapus karena sudah ada siswa yang memulai ujian.');
        }

        if ($question->image_path) {
            Storage::disk('public')->delete($question->image_path);
        }
        foreach ($question->options as $opt) {
            $this->deleteOptionImage($opt);
        }
        $question->delete();

        return redirect()->back()->with('success', 'Soal berhasil dihapus.');
    }

    /**
     * Buat ulang opsi PG dari nol (dipakai saat menambah soal baru).
     * Tiap opsi bisa punya teks (boleh rumus $…$) dan/atau gambar.
     */
    private function syncOptions(Question $question, Request $request): void
    {
        $correct = (int) $request->input('correct', 0);
        $i = 0;

        foreach ((array) $request->input('options', []) as $idx => $text) {
            $text = trim((string) $text);
            $hasFile = $request->hasFile("option_images.$idx");
            if ($text === '' && !$hasFile) {
                continue; // baris kosong (tanpa teks & tanpa gambar) dilewati
            }

            $question->options()->create([
                'label' => chr(65 + $i),
                'option_text' => $text,
                'image_path' => $hasFile ? $request->file("option_images.$idx")->store('exam-options', 'public') : null,
                'is_correct' => ($idx === $correct),
                'order' => $i,
            ]);
            $i++;
        }

        $this->ensureOneCorrect($question);
    }

    /**
     * Perbarui opsi PG sambil MEMPERTAHANKAN gambar opsi yang sudah ada
     * (opsi dicocokkan lewat option_ids[]). Gambar diganti bila di-upload baru,
     * dihapus bila dicentang "Hapus gambar", dan opsi yang hilang ikut dibersihkan.
     */
    private function syncOptionsUpdate(Question $question, Request $request): void
    {
        $correct = (int) $request->input('correct', 0);
        $ids = (array) $request->input('option_ids', []);
        $removeImg = (array) $request->input('option_remove_image', []); // berisi ID opsi
        $keep = [];
        $i = 0;

        foreach ((array) $request->input('options', []) as $idx => $text) {
            $text = trim((string) $text);
            $existing = !empty($ids[$idx]) ? $question->options()->find($ids[$idx]) : null;
            $hasFile = $request->hasFile("option_images.$idx");
            $imgPath = $existing?->image_path;

            // Baris benar-benar kosong → buang (termasuk opsi lama bila ada).
            if ($text === '' && !$hasFile && !$imgPath) {
                if ($existing) {
                    $this->deleteOptionImage($existing);
                    $existing->delete();
                }
                continue;
            }

            if ($existing && $imgPath && in_array($existing->id, $removeImg, true)) {
                Storage::disk('public')->delete($imgPath);
                $imgPath = null;
            }
            if ($hasFile) {
                if ($imgPath) {
                    Storage::disk('public')->delete($imgPath);
                }
                $imgPath = $request->file("option_images.$idx")->store('exam-options', 'public');
            }

            $payload = [
                'label' => chr(65 + $i),
                'option_text' => $text,
                'image_path' => $imgPath,
                'is_correct' => ($idx === $correct),
                'order' => $i,
            ];

            if ($existing) {
                $existing->update($payload);
                $keep[] = $existing->id;
            } else {
                $keep[] = $question->options()->create($payload)->id;
            }
            $i++;
        }

        // Bersihkan opsi lama yang tidak lagi dikirim.
        foreach ($question->options()->whereNotIn('id', $keep ?: ['00000000-0000-0000-0000-000000000000'])->get() as $orphan) {
            $this->deleteOptionImage($orphan);
            $orphan->delete();
        }

        $this->ensureOneCorrect($question);
    }

    /** Pastikan minimal ada satu opsi ditandai sebagai kunci jawaban. */
    private function ensureOneCorrect(Question $question): void
    {
        if (!$question->options()->where('is_correct', true)->exists()) {
            $first = $question->options()->orderBy('order')->first();
            if ($first) {
                $first->update(['is_correct' => true]);
            }
        }
    }

    private function deleteOptionImage(QuestionOption $option): void
    {
        if ($option->image_path) {
            try {
                Storage::disk('public')->delete($option->image_path);
            } catch (\Throwable $e) {
                // abaikan — file mungkin sudah tidak ada
            }
        }
    }

    private function imageMessages(): array
    {
        return [
            'image.image' => 'Berkas yang diunggah harus berupa gambar.',
            'image.mimes' => 'Format gambar harus JPG, JPEG, atau PNG.',
            'image.max' => 'Ukuran gambar maksimal 3 MB.',
            'option_images.*.image' => 'Gambar opsi harus berupa berkas gambar.',
            'option_images.*.mimes' => 'Format gambar opsi harus JPG, JPEG, atau PNG.',
            'option_images.*.max' => 'Ukuran gambar opsi maksimal 3 MB.',
        ];
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
