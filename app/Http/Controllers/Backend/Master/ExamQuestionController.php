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
            'points' => 'nullable|numeric|min:0',
            'penalty' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:3072',
            'option_images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:3072',
        ], $this->imageMessages());

        $exam = Exam::findOrFail($request->exam_id);
        $this->authorizeExam($exam);

        if ($exam->hasStartedAttempts()) {
            return redirect()->back()->with('error', 'Soal tidak bisa ditambah karena sudah ada siswa yang memulai ujian.');
        }

        // Bobot essay mode MANUAL diisi guru di sini, saat membuat soal — permintaan
        // sekolah: waktu mengoreksi, nilai maksimal tiap soal sudah tertera. Pada mode
        // otomatis kolom ini tidak ada di form dan bobot dibagi rata oleh sistem.
        if ($galat = $this->periksaBobotEssay($request, $exam)) {
            return $galat;
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
                    'points' => $this->bobotDisimpan($request, $exam),
                    'penalty' => $request->penalty ?: 0,
                    'order' => ($exam->questions()->max('order') ?? 0) + 1,
                ];

                if ($request->hasFile('image')) {
                    $data['image_path'] = \App\Support\GambarSoal::kecilkan(
                        $request->file('image')->store('exam-questions', 'public'));
                }

                $question = Question::create($data);

                if ($request->type === 'mc') {
                    $this->syncOptions($question, $request);
                }

                // Soal yang dibuat saat menyusun ujian ikut masuk Bank Soal Bersama.
                \App\Support\BankSoal::cerminkan($question->fresh('options'), $exam);
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
            'points' => 'nullable|numeric|min:0',
            'penalty' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:3072',
            'option_images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:3072',
        ], $this->imageMessages());

        // Bobot soal yang sedang diedit dikecualikan dari total, kalau tidak nilai
        // yang sama pun akan tertolak karena dihitung dua kali.
        if ($galat = $this->periksaBobotEssay($request, $question->exam, $question)) {
            return $galat;
        }

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
                    'points' => $this->bobotDisimpan($request, $question->exam, $question),
                    'penalty' => $request->penalty ?: 0,
                ];

                if ($request->hasFile('image')) {
                    if ($question->image_path) {
                        Storage::disk('public')->delete($question->image_path);
                    }
                    $data['image_path'] = \App\Support\GambarSoal::kecilkan(
                        $request->file('image')->store('exam-questions', 'public'));
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

    /** Salin soal terpilih dari Bank Soal Bersama ke dalam ujian ini. */
    public function pullFromBank(Request $request, $examId)
    {
        $exam = Exam::findOrFail($examId);
        $this->authorizeExam($exam);

        if ($exam->hasStartedAttempts()) {
            return back()->with('error', 'Soal tidak bisa ditambah karena sudah ada siswa yang memulai ujian.');
        }

        $request->validate(['bank_ids' => 'required|array|min:1'], [], ['bank_ids' => 'Soal Bank']);

        $banks = \App\Models\QuestionBank::with('options')->whereIn('id', $request->bank_ids)->get();
        $copied = 0;
        $skipped = 0;
        $order = (int) ($exam->questions()->max('order') ?? 0);

        DB::transaction(function () use ($banks, $exam, &$copied, &$skipped, &$order) {
            foreach ($banks as $bank) {
                if (($bank->type === 'mc' && !$exam->hasMc()) || ($bank->type === 'essay' && !$exam->hasEssay())) {
                    $skipped++;
                    continue;
                }
                $q = Question::create([
                    'exam_id' => $exam->id,
                    'type' => $bank->type,
                    'question_text' => $bank->question_text,
                    'image_path' => $this->copyImage($bank->image_path),
                    'points' => $bank->points,
                    'penalty' => $bank->type === 'mc' ? $bank->penalty : 0,
                    'order' => ++$order,
                ]);
                if ($bank->type === 'mc') {
                    foreach ($bank->options as $opt) {
                        $q->options()->create([
                            'label' => $opt->label,
                            'option_text' => $opt->option_text,
                            'image_path' => $this->copyImage($opt->image_path),
                            'is_correct' => $opt->is_correct,
                            'order' => $opt->order,
                        ]);
                    }
                }

                // Salinan ikut masuk Bank Soal sebagai milik sekolah ujian ini,
                // dengan jejak sekolah sumbernya (badge "sumber: ...").
                \App\Support\BankSoal::cerminkan($q->fresh('options'), $exam, $bank);

                $copied++;
            }
        });

        if ($copied === 0) {
            return back()->with('error', 'Tidak ada soal yang disalin.' . ($skipped ? " ($skipped soal tidak sesuai kategori ujian dilewati.)" : ''));
        }
        return back()->with('success', "$copied soal berhasil disalin dari Bank Soal." . ($skipped ? " $skipped dilewati (beda kategori)." : ''));
    }

    /** Salin file gambar (agar independen dari Bank Soal). Kembalikan path baru atau null. */
    private function copyImage(?string $src): ?string
    {
        if (!$src) {
            return null;
        }
        try {
            $disk = Storage::disk('public');
            if (!$disk->exists($src)) {
                return null;
            }
            $ext = pathinfo($src, PATHINFO_EXTENSION) ?: 'png';
            $dest = 'exam-questions/' . \Illuminate\Support\Str::uuid() . '.' . $ext;
            $disk->copy($src, $dest);
            return $dest;
        } catch (\Throwable $e) {
            return null;
        }
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
                'image_path' => $hasFile ? \App\Support\GambarSoal::kecilkan(
                    $request->file("option_images.$idx")->store('exam-options', 'public')) : null,
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
                $imgPath = \App\Support\GambarSoal::kecilkan(
                    $request->file("option_images.$idx")->store('exam-options', 'public'));
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

    /**
     * Apakah soal ini memakai bobot yang diisi guru? Hanya essay pada ujian
     * bermode manual. Di luar itu bobot dihitung sistem (bagi rata) dan kolom
     * bobot tidak ditampilkan di form.
     */
    private function bobotDiisiGuru(Request $request, Exam $exam, ?Question $question = null): bool
    {
        $tipe = $question->type ?? $request->type;

        return $tipe === 'essay' && $exam->points_mode !== 'auto';
    }

    /**
     * Periksa bobot essay yang dikirim guru. Mengembalikan RedirectResponse bila
     * tidak sah, atau null bila lolos.
     *
     * Aturannya sama seperti yang sudah dipakai saat memeriksa jawaban: total
     * bobot seluruh soal essay tidak boleh melewati 100, karena nilai bagian
     * Essay memang berskala 0–100 (lihat Exam::essayMaxPoints()). Batas ini
     * ditegakkan di sini supaya guru tahu sisa jatahnya SAAT membuat soal,
     * bukan baru ketahuan waktu mengoreksi.
     */
    private function periksaBobotEssay(Request $request, Exam $exam, ?Question $question = null)
    {
        if (!$this->bobotDiisiGuru($request, $exam, $question)) {
            return null;
        }

        $bobot = (float) $request->input('points');
        if ($bobot <= 0) {
            return redirect()->back()->withInput()
                ->with('error', 'Bobot (nilai maksimal) soal essay wajib diisi dan harus lebih dari 0.');
        }
        if ($bobot > 100) {
            return redirect()->back()->withInput()
                ->with('error', 'Bobot satu soal essay tidak boleh lebih dari 100.');
        }

        $sisa = $exam->sisaBobotEssay($question->id ?? null);
        if ($bobot > $sisa + 0.01) {
            $f = fn ($v) => rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');

            return redirect()->back()->withInput()->with('error',
                'Bobot ' . $f($bobot) . ' melebihi sisa jatah bobot essay yang tersedia (' . $f($sisa) . ' dari total 100). '
                . 'Kurangi bobot soal ini atau ubah bobot soal essay lain lebih dulu.');
        }

        return null;
    }

    /** Nilai yang benar-benar disimpan ke kolom points. */
    private function bobotDisimpan(Request $request, Exam $exam, ?Question $question = null): float
    {
        return $this->bobotDiisiGuru($request, $exam, $question)
            ? round((float) $request->input('points'), 2)
            : 1.0;   // tidak dipakai pada mode otomatis / soal PG
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
