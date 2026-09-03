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
use Illuminate\Support\Facades\Storage;

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
            ->where('is_active', true)
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
        $session = ExamSession::with('exam.questions.options')->findOrFail($sessionId);

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
        // Timer = durasi penuh sejak siswa MULAI. Jam sesi (starts_at–ends_at) hanya
        // membatasi KAPAN siswa boleh masuk; durasi tidak dipotong walau telat masuk.
        $ends = $now->copy()->addMinutes($session->duration_minutes);

        ExamAttempt::create([
            'exam_session_id' => $session->id,
            'student_id' => $student->id,
            'started_at' => $now,
            'ends_at' => $ends,
            'status' => 'in_progress',
            // Urutan soal & opsi DIKUNCI saat mulai → tidak teracak ulang saat refresh/pindah soal.
            'layout' => json_encode($this->buildLayout($session)),
        ]);

        return redirect()->route('student.exams.attempt', $session->id);
    }

    /** Tentukan urutan soal & opsi (acak bila diaktifkan) — disimpan sekali per attempt. */
    private function buildLayout(ExamSession $session): array
    {
        $questions = $session->exam->questions;
        $qOrder = $session->shuffle_questions
            ? $questions->shuffle()->pluck('id')->all()
            : $questions->pluck('id')->all();

        $oOrder = [];
        foreach ($questions as $q) {
            if ($q->type === 'mc') {
                $opts = $session->shuffle_options ? $q->options->shuffle() : $q->options;
                $oOrder[$q->id] = $opts->pluck('id')->all();
            }
        }

        return ['q' => $qOrder, 'o' => $oOrder];
    }

    /** Halaman pengerjaan. */
    public function attempt($sessionId)
    {
        $student = auth()->user()->student;
        $session = ExamSession::with([
            'exam.questions.options',
            'exam.teachingAssignment.subject',
            'exam.teachingAssignment.classRoom',
            'classRoom',
        ])->findOrFail($sessionId);

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

        // Waktu habis → auto submit (kecuali sedang terkunci: timer dijeda menunggu PIN guru).
        if (!$attempt->locked_at && Carbon::now()->gte($attempt->ends_at)) {
            CbtScoringService::finalizeOnSubmit($attempt);
            $this->afterFinalize($attempt);
            return redirect()->route('student.exams.result', $attempt->id)->with('error', 'Waktu ujian telah habis, jawaban otomatis dikumpulkan.');
        }

        $exam = $session->exam;

        // Urutan terkunci dari saat mulai (disimpan di attempt->layout). Fallback bila kosong.
        $layout = $attempt->layout ? json_decode($attempt->layout, true) : null;
        if (!$layout || empty($layout['q'])) {
            $layout = $this->buildLayout($session);
            $attempt->update(['layout' => json_encode($layout)]);
        }

        $byId = $exam->questions->keyBy('id');
        $questions = collect($layout['q'])->map(fn ($qid) => $byId->get($qid))->filter()->values();

        // Susun ulang opsi tiap soal sesuai urutan tersimpan (tidak diacak ulang).
        foreach ($questions as $q) {
            if ($q->type === 'mc' && !empty($layout['o'][$q->id])) {
                $optById = $q->options->keyBy('id');
                $ordered = collect($layout['o'][$q->id])->map(fn ($oid) => $optById->get($oid))->filter()->values();
                $q->setRelation('options', $ordered);
            }
        }

        $answers = $attempt->answers()->get()->keyBy('question_id');
        // Saat terkunci timer dijeda → sisa waktu dibekukan pada saat mulai terkunci.
        $remaining = $attempt->locked_at
            ? max(0, Carbon::parse($attempt->locked_at)->diffInSeconds($attempt->ends_at, false))
            : max(0, Carbon::now()->diffInSeconds($attempt->ends_at, false));

        return view('frontend.exams.attempt', compact('session', 'exam', 'attempt', 'questions', 'answers', 'remaining')
            + ['hideChrome' => true, 'isLocked' => (bool) $attempt->locked_at]);
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
        if ($attempt->locked_at) {
            return response()->json(['message' => 'Sesi terkunci. Masukkan PIN untuk melanjutkan.', 'locked' => true], 422);
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

    /** Unggah foto jawaban essay (maks 3 per soal). */
    public function uploadPhoto(Request $request)
    {
        $student = auth()->user()->student;
        $attempt = ExamAttempt::findOrFail($request->attempt_id);
        if ($attempt->student_id !== $student->id) return response()->json(['message' => 'Akses ditolak.'], 403);
        if ($attempt->status !== 'in_progress') return response()->json(['message' => 'Ujian sudah dikumpulkan.'], 422);
        if ($attempt->locked_at) return response()->json(['message' => 'Sesi terkunci. Masukkan PIN untuk melanjutkan.', 'locked' => true], 422);
        if (Carbon::now()->gte($attempt->ends_at)) return response()->json(['message' => 'Waktu habis.', 'expired' => true], 422);

        $request->validate([
            'question_id' => 'required|uuid|exists:questions,id',
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:1024',   // 1 MB — foto sudah dikecilkan di HP sebelum dikirim
        ]);

        $ans = ExamAnswer::firstOrNew(['exam_attempt_id' => $attempt->id, 'question_id' => $request->question_id]);
        $imgs = $ans->answer_images ?: [];
        if (count($imgs) >= 3) {
            return response()->json(['message' => 'Maksimal 3 foto per soal.'], 422);
        }
        $path = $request->file('photo')->store('exam-answers', 'public');
        // Foto kamera HP bisa 3–12 MP; kalau disimpan mentah, memuatnya di halaman
        // koreksi jadi sangat lambat. Kecilkan + buat thumbnail untuk pratinjau.
        $this->kompresFoto($path);
        $imgs[] = $path;
        $ans->answer_images = $imgs;
        $ans->save();

        return response()->json([
            'message' => 'Foto terunggah.',
            'images' => array_map(fn ($p) => self::fotoUrl($p), $imgs),
        ]);
    }

    /**
     * Perkecil foto jawaban (sisi terpanjang maks 1600px, JPEG mutu 78) lalu buat
     * thumbnail 400px bernama <nama>_thumb.jpg. Dipakai untuk pratinjau grid.
     * Memakai GD (tanpa paket tambahan); bila gagal, berkas asli dibiarkan apa adanya.
     */
    private function kompresFoto(string $path): void
    {
        try {
            $full = Storage::disk('public')->path($path);
            if (!is_file($full)) {
                return;
            }
            $info = @getimagesize($full);
            if (!$info) {
                return;
            }
            [$w, $h] = $info;
            $src = match ($info['mime']) {
                'image/jpeg' => @imagecreatefromjpeg($full),
                'image/png'  => @imagecreatefrompng($full),
                default      => null,
            };
            if (!$src) {
                return;
            }

            $buat = function ($maks, $tujuan) use ($src, $w, $h) {
                $skala = min(1, $maks / max($w, $h));
                $nw = max(1, (int) round($w * $skala));
                $nh = max(1, (int) round($h * $skala));
                $dst = imagecreatetruecolor($nw, $nh);
                imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // PNG transparan → putih
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                imagejpeg($dst, $tujuan, 78);
                imagedestroy($dst);
            };

            $buat(1600, $full);                                     // ganti berkas asli (lebih kecil)
            $buat(400, Storage::disk('public')->path(self::thumbPath($path)));
            imagedestroy($src);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Kompres foto jawaban gagal: '.$e->getMessage());
        }
    }

    /** Nama berkas thumbnail dari path foto. */
    private static function thumbPath(string $path): string
    {
        return preg_replace('/\.[^.]+$/', '', $path).'_thumb.jpg';
    }

    /** Bentuk data foto untuk frontend: url penuh + url thumbnail (bila ada). */
    private static function fotoUrl(string $p): array
    {
        $thumb = self::thumbPath($p);
        return [
            'path' => $p,
            'url' => asset('storage/'.$p),
            'thumb' => Storage::disk('public')->exists($thumb) ? asset('storage/'.$thumb) : asset('storage/'.$p),
        ];
    }

    /** Hapus satu foto jawaban essay (sebelum submit). */
    public function deletePhoto(Request $request)
    {
        $student = auth()->user()->student;
        $attempt = ExamAttempt::findOrFail($request->attempt_id);
        if ($attempt->student_id !== $student->id) return response()->json(['message' => 'Akses ditolak.'], 403);
        if ($attempt->status !== 'in_progress') return response()->json(['message' => 'Ujian sudah dikumpulkan.'], 422);

        $request->validate(['question_id' => 'required|uuid', 'path' => 'required|string']);

        $ans = ExamAnswer::where('exam_attempt_id', $attempt->id)->where('question_id', $request->question_id)->first();
        if ($ans) {
            $imgs = array_values(array_filter($ans->answer_images ?: [], fn ($p) => $p !== $request->path));
            $ans->answer_images = $imgs;
            $ans->save();
            try { Storage::disk('public')->delete([$request->path, self::thumbPath($request->path)]); } catch (\Throwable $e) {}
            return response()->json(['message' => 'Foto dihapus.', 'images' => array_map(fn ($p) => self::fotoUrl($p), $imgs)]);
        }
        return response()->json(['message' => 'Tidak ditemukan.', 'images' => []]);
    }

    /**
     * Siswa keluar dari layar ujian tapi KEMBALI dalam tenggang waktu → tidak dikunci,
     * hanya dicatat (leave_count) supaya guru tahu siapa yang sering keluar.
     * Setelah melewati batas toleransi, klien akan langsung mengunci (lock()).
     */
    public function leaveWarning(Request $request)
    {
        $student = auth()->user()->student;
        $attempt = ExamAttempt::findOrFail($request->attempt_id);
        if (!$student || $attempt->student_id !== $student->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }
        if ($attempt->status === 'in_progress' && !$attempt->locked_at) {
            $attempt->leave_count = (int) $attempt->leave_count + 1;
            $attempt->save();
        }
        return response()->json(['leave_count' => (int) $attempt->leave_count]);
    }

    /** Kunci attempt karena siswa keluar dari layar ujian (pindah tab / keluar fullscreen). Timer dijeda. */
    public function lock(Request $request)
    {
        $student = auth()->user()->student;
        $attempt = ExamAttempt::findOrFail($request->attempt_id);
        if (!$student || $attempt->student_id !== $student->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }
        if ($attempt->status === 'in_progress' && !$attempt->locked_at) {
            $attempt->locked_at = Carbon::now();
            $attempt->lock_count = (int) $attempt->lock_count + 1;
            $attempt->save();
        }
        return response()->json(['locked' => true]);
    }

    /** Buka kunci dengan PIN sesi (dari guru). Timer dilanjutkan — ends_at diperpanjang selama durasi jeda. */
    public function unlock(Request $request)
    {
        $student = auth()->user()->student;
        $attempt = ExamAttempt::with('session')->findOrFail($request->attempt_id);
        if (!$student || $attempt->student_id !== $student->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }
        $request->validate(['pin' => 'required|string']);

        $pin = $attempt->session->resume_pin ?? null;
        if (!$pin || trim((string) $request->pin) !== (string) $pin) {
            return response()->json(['message' => 'PIN salah. Minta PIN yang benar kepada pengawas/guru.'], 422);
        }

        if ($attempt->locked_at) {
            $delta = (int) round(abs(Carbon::parse($attempt->locked_at)->diffInSeconds(Carbon::now())));
            $attempt->ends_at = Carbon::parse($attempt->ends_at)->addSeconds($delta);
            $attempt->paused_seconds = (int) $attempt->paused_seconds + $delta;
            $attempt->locked_at = null;
            $attempt->save();
        }

        $remaining = (int) max(0, Carbon::now()->diffInSeconds($attempt->ends_at, false));
        return response()->json(['ok' => true, 'remaining' => $remaining]);
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
