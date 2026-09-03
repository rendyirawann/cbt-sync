<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\QuestionBank;
use App\Models\QuestionBankOption;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

/**
 * Bank Soal Bersama — soal reusable LINTAS SEKOLAH (pengecualian dari batas sekolah).
 * Guru mana pun bisa menambah & menarik soal ke ujiannya.
 */
class QuestionBankController extends Controller
{
    public function index(Request $request)
    {
        // Filter yang sama dipakai dua kali: untuk daftar kelompok dan untuk isinya.
        $filter = fn ($q) => $q
            ->when($request->filled('school_id'), fn ($x) => $x->where('school_id', $request->school_id))
            ->when($request->filled('subject_id'), fn ($x) => $x->where('subject_id', $request->subject_id))
            ->when($request->filled('level'), fn ($x) => $x->where('level', $request->level))
            ->when($request->filled('type'), fn ($x) => $x->where('type', $request->type))
            ->when($request->filled('search'), fn ($x) => $x->where('question_text', 'ilike', '%' . $request->search . '%'));

        // Bank ini memang lintas sekolah (tidak discope): filter sekolah dipakai
        // untuk MELIHAT soal buatan sekolah tertentu, bukan untuk membatasi akses.
        // Yang dipaginasi adalah KELOMPOK ujian, bukan soal satu per satu.
        $groups = QuestionBank::query()
            ->selectRaw('source_exam_id, source_exam_title, count(*) as jumlah, max(created_at) as terakhir')
            ->tap($filter)
            ->groupBy('source_exam_id', 'source_exam_title')
            ->orderByDesc('terakhir')
            ->paginate(10)
            ->withQueryString();

        // Isi seluruh kelompok di halaman ini diambil sekali jalan, lalu dipetakan
        // per ujian agar tampilan tidak melakukan kueri di dalam loop.
        $idUjian = $groups->pluck('source_exam_id');
        $items = QuestionBank::with(['subject', 'options', 'creator', 'school', 'sourceSchool'])
            ->tap($filter)
            ->where(function ($q) use ($idUjian) {
                $q->whereIn('source_exam_id', $idUjian->filter()->values()->all());
                if ($idUjian->contains(null)) {
                    $q->orWhereNull('source_exam_id');
                }
            })
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn ($b) => $b->source_exam_id ?? 'tanpa');

        $subjects = Subject::orderBy('name')->get();
        $levels = QuestionBank::whereNotNull('level')->distinct()->orderBy('level')->pluck('level');
        $schools = \App\Models\School::orderBy('name')->get();

        return view('backend.master.question-banks.index', compact('groups', 'items', 'subjects', 'levels', 'schools'));
    }

    public function store(Request $request)
    {
        $request->validate($this->rules(), [], $this->labels());
        if ($request->type === 'mc') {
            $request->validate(['options' => 'required|array|min:2', 'correct' => 'required'], [], ['options' => 'Opsi']);
        }

        try {
            DB::transaction(function () use ($request) {
                $data = [
                    'subject_id' => $request->subject_id,
                    'level' => $request->level,
                    'type' => $request->type,
                    'question_text' => $request->question_text,
                    'points' => $request->points,
                    'penalty' => $request->penalty ?: 0,
                    'created_by' => auth()->id(),
                ];
                if ($request->hasFile('image')) {
                    $data['image_path'] = $request->file('image')->store('question-banks', 'public');
                }
                $bank = QuestionBank::create($data);
                if ($request->type === 'mc') {
                    $this->syncOptions($bank, $request);
                }
            });
            return back()->with('success', 'Soal berhasil ditambahkan ke Bank Soal.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $bank = QuestionBank::findOrFail($id);
        $request->validate($this->rules(), [], $this->labels());
        if ($bank->type === 'mc') {
            $request->validate(['options' => 'required|array|min:2', 'correct' => 'required'], [], ['options' => 'Opsi']);
        }

        try {
            DB::transaction(function () use ($request, $bank) {
                $data = [
                    'subject_id' => $request->subject_id,
                    'level' => $request->level,
                    'question_text' => $request->question_text,
                    'points' => $request->points,
                    'penalty' => $request->penalty ?: 0,
                ];
                if ($request->hasFile('image')) {
                    if ($bank->image_path) {
                        Storage::disk('public')->delete($bank->image_path);
                    }
                    $data['image_path'] = $request->file('image')->store('question-banks', 'public');
                }
                $bank->update($data);
                if ($bank->type === 'mc') {
                    $this->syncOptionsUpdate($bank, $request);
                }
            });
            return back()->with('success', 'Soal Bank berhasil diperbarui.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memperbarui: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $bank = QuestionBank::with('options')->findOrFail($id);
        if ($bank->image_path) {
            Storage::disk('public')->delete($bank->image_path);
        }
        foreach ($bank->options as $opt) {
            if ($opt->image_path) {
                Storage::disk('public')->delete($opt->image_path);
            }
        }
        $bank->delete();
        return back()->with('success', 'Soal Bank berhasil dihapus.');
    }

    private function rules(): array
    {
        return [
            'subject_id' => 'required|uuid|exists:subjects,id',
            'level' => 'nullable|string|max:50',
            'type' => 'required|in:mc,essay',
            'question_text' => 'required|string',
            'points' => 'required|numeric|min:0',
            'penalty' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:3072',
            'option_images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:3072',
        ];
    }

    private function labels(): array
    {
        return ['subject_id' => 'Mata Pelajaran', 'question_text' => 'Pertanyaan', 'points' => 'Poin', 'type' => 'Tipe'];
    }

    private function syncOptions(QuestionBank $bank, Request $request): void
    {
        $correct = (int) $request->input('correct', 0);
        $i = 0;
        foreach ((array) $request->input('options', []) as $idx => $text) {
            $text = trim((string) $text);
            $hasFile = $request->hasFile("option_images.$idx");
            if ($text === '' && !$hasFile) {
                continue;
            }
            $bank->options()->create([
                'label' => chr(65 + $i),
                'option_text' => $text,
                'image_path' => $hasFile ? $request->file("option_images.$idx")->store('question-banks', 'public') : null,
                'is_correct' => ($idx === $correct),
                'order' => $i,
            ]);
            $i++;
        }
        $this->ensureOneCorrect($bank);
    }

    private function syncOptionsUpdate(QuestionBank $bank, Request $request): void
    {
        $correct = (int) $request->input('correct', 0);
        $ids = (array) $request->input('option_ids', []);
        $removeImg = (array) $request->input('option_remove_image', []);
        $keep = [];
        $i = 0;
        foreach ((array) $request->input('options', []) as $idx => $text) {
            $text = trim((string) $text);
            $existing = !empty($ids[$idx]) ? $bank->options()->find($ids[$idx]) : null;
            $hasFile = $request->hasFile("option_images.$idx");
            $imgPath = $existing?->image_path;

            if ($text === '' && !$hasFile && !$imgPath) {
                if ($existing) {
                    $this->deleteOptImg($existing);
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
                $imgPath = $request->file("option_images.$idx")->store('question-banks', 'public');
            }
            $payload = ['label' => chr(65 + $i), 'option_text' => $text, 'image_path' => $imgPath, 'is_correct' => ($idx === $correct), 'order' => $i];
            if ($existing) {
                $existing->update($payload);
                $keep[] = $existing->id;
            } else {
                $keep[] = $bank->options()->create($payload)->id;
            }
            $i++;
        }
        foreach ($bank->options()->whereNotIn('id', $keep ?: ['00000000-0000-0000-0000-000000000000'])->get() as $orphan) {
            $this->deleteOptImg($orphan);
            $orphan->delete();
        }
        $this->ensureOneCorrect($bank);
    }

    private function ensureOneCorrect(QuestionBank $bank): void
    {
        if (!$bank->options()->where('is_correct', true)->exists()) {
            $first = $bank->options()->orderBy('order')->first();
            if ($first) {
                $first->update(['is_correct' => true]);
            }
        }
    }

    private function deleteOptImg(QuestionBankOption $opt): void
    {
        if ($opt->image_path) {
            try {
                Storage::disk('public')->delete($opt->image_path);
            } catch (\Throwable $e) {
            }
        }
    }
}
