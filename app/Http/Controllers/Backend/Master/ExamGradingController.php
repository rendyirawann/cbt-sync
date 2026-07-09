<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAnswer;
use App\Models\ExamSession;
use App\Models\Notification;
use App\Services\CbtScoringService;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExamGradingController extends Controller
{
    /** Daftar peserta sebuah sesi + status pengerjaan/nilai. */
    public function attempts($sessionId)
    {
        $session = ExamSession::with([
            'exam.teachingAssignment.subject', 'exam.teachingAssignment.classRoom',
            'classRoom', 'attempts.student.user',
        ])->findOrFail($sessionId);

        $this->authorizeExam($session->exam);

        $eligible = $session->eligibleStudents();
        $attemptsByStudent = $session->attempts->keyBy('student_id');

        return view('backend.master.exams.attempts', compact('session', 'eligible', 'attemptsByStudent'));
    }

    /** Halaman periksa satu attempt (koreksi essay + lihat hasil PG). */
    public function grade($attemptId)
    {
        $attempt = ExamAttempt::with([
            'session.exam.questions.options', 'student.user', 'answers',
        ])->findOrFail($attemptId);

        $this->authorizeExam($attempt->session->exam);

        $exam = $attempt->session->exam;
        $count = $exam->questions->count();
        $answers = $attempt->answers->keyBy('question_id');

        // Bobot (skor maks) tiap soal sesuai mode penilaian.
        $weights = [];
        foreach ($exam->questions as $q) {
            $weights[$q->id] = CbtScoringService::questionWeight($exam, $q, $count);
        }

        return view('backend.master.exams.grade', compact('attempt', 'exam', 'answers', 'weights'));
    }

    /** Simpan nilai essay (+ nilai akhir manual), lalu akumulasi & notifikasi. */
    public function storeGrade(Request $request, $attemptId)
    {
        $attempt = ExamAttempt::with(['session.exam.questions', 'answers'])->findOrFail($attemptId);
        $this->authorizeExam($attempt->session->exam);

        // Setelah dinilai (graded), hanya Superadmin yang boleh mengubah (koreksi). Guru tidak.
        if ($attempt->status === 'graded' && !auth()->user()->hasRole('Superadmin')) {
            return redirect()->back()->with('error', 'Nilai sudah final. Hanya Superadmin yang dapat mengubah nilai.');
        }

        $exam = $attempt->session->exam;
        $count = $exam->questions->count();
        $scores = $request->input('scores', []);     // [question_id => nilai]
        $feedbacks = $request->input('feedback', []); // [question_id => catatan]

        foreach ($exam->questions->where('type', 'essay') as $q) {
            $weight = CbtScoringService::questionWeight($exam, $q, $count);
            $val = isset($scores[$q->id]) ? (float) $scores[$q->id] : 0;
            $val = max(0, min($val, $weight)); // clamp 0..maks

            ExamAnswer::updateOrCreate(
                ['exam_attempt_id' => $attempt->id, 'question_id' => $q->id],
                ['earned_score' => round($val, 2), 'feedback' => $feedbacks[$q->id] ?? null, 'graded' => true]
            );
        }

        if ($exam->points_mode === 'manual') {
            $request->validate(['final_score' => 'required|numeric|min:0']);
            $essayScore = (float) $attempt->answers()
                ->whereHas('question', fn ($x) => $x->where('type', 'essay'))->sum('earned_score');
            $attempt->update([
                'essay_score' => round($essayScore, 2),
                'total_score' => round((float) $attempt->mc_score + $essayScore, 2),
                'final_score' => round((float) $request->final_score, 2),
                'essay_graded' => true,
                'status' => 'graded',
            ]);
        } else {
            CbtScoringService::recomputeAfterEssayGrading($attempt);
        }

        $attempt->refresh();
        $this->notifyResult($attempt);
        GamificationService::evaluateExamScore($attempt);

        return redirect()->route('exam-sessions.attempts', $attempt->exam_session_id)
            ->with('success', 'Nilai berhasil disimpan. Nilai akhir: ' . rtrim(rtrim((string) $attempt->final_score, '0'), '.'));
    }

    /** Ekspor hasil ujian satu sesi ke Excel (laporan ber-template). */
    public function exportResults($sessionId)
    {
        $session = ExamSession::with([
            'exam.teachingAssignment.subject', 'exam.teachingAssignment.classRoom', 'exam.teachingAssignment.teacher.user',
            'exam.questions', 'classRoom', 'attempts.student.user', 'attempts.student.school', 'attempts.answers',
        ])->findOrFail($sessionId);
        $this->authorizeExam($session->exam);

        $exam = $session->exam;
        $questions = $exam->questions->sortBy('order')->values();
        $qCount = $questions->count();
        $weights = [];
        foreach ($questions as $q) {
            $weights[$q->id] = CbtScoringService::questionWeight($exam, $q, $qCount);
        }
        $attempts = $session->attempts->sortBy(fn ($a) => $a->student->user->name ?? '')->values();
        $pass = (float) ($exam->pass_score ?? 0);

        $num = fn ($v) => $v === null ? '' : rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        $col = fn ($i) => Coordinate::stringFromColumnIndex($i);

        $firstQ = 4;                          // kolom D = soal pertama
        $sumCol = $firstQ + $qCount;          // kolom ringkasan pertama (Benar)
        $totalCols = 3 + $qCount + 7;         // No,Nama,Kelas + N soal + 7 ringkasan
        $lastColL = $col($totalCols);

        $ss = new Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Hasil Ujian');
        $siteName = \App\Models\Setting::get('site_name', config('seo.title'));

        // ---------- Judul ----------
        $sheet->mergeCells("A1:{$lastColL}1");
        $sheet->setCellValue('A1', 'LAPORAN HASIL UJIAN');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF4F46E5');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->mergeCells("A2:{$lastColL}2");
        $sheet->setCellValue('A2', $siteName);
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A2')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF6D28D9');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ---------- Info ----------
        $kelas = $session->class_room_id ? ($session->classRoom->name ?? '-') : ($exam->teachingAssignment->classRoom->name ?? 'Lintas kelas');
        $guru = $exam->teachingAssignment->teacher->user->name ?? '-';
        $info = [
            ['Ujian', $exam->title],
            ['Mata Pelajaran', $exam->teachingAssignment->subject->name ?? '-'],
            ['Kelas', $kelas],
            ['Sesi', $session->name],
            ['Jadwal', Carbon::parse($session->starts_at)->format('d M Y H:i') . ' - ' . Carbon::parse($session->ends_at)->format('H:i') . '  (' . $session->duration_minutes . ' menit)'],
            ['KKM', $num($exam->pass_score)],
            ['Guru', $guru],
            ['Dicetak', Carbon::now()->format('d M Y H:i')],
        ];
        $r = 4;
        foreach ($info as $it) {
            $sheet->setCellValue("A{$r}", $it[0]);
            $sheet->getStyle("A{$r}")->getFont()->setBold(true);
            $sheet->mergeCells("B{$r}:{$lastColL}{$r}");
            $sheet->setCellValue("B{$r}", $it[1]);
            $r++;
        }

        // ---------- Header tabel ----------
        $hr = $r + 1;                         // baris header
        $sheet->setCellValue("A{$hr}", 'No');
        $sheet->setCellValue("B{$hr}", 'Nama Siswa');
        $sheet->setCellValue("C{$hr}", 'Kelas');
        foreach ($questions as $qi => $q) {
            $c = $col($firstQ + $qi);
            $type = $q->type === 'mc' ? 'PG' : 'Esai';
            $sheet->setCellValue("{$c}{$hr}", 'S' . ($qi + 1) . " ({$type})\nmaks " . $num($weights[$q->id]));
        }
        $labels = ['Benar', 'Salah', 'Kosong', 'Nilai PG', 'Nilai Esai', 'Nilai Akhir', 'Status'];
        foreach ($labels as $li => $lab) {
            $sheet->setCellValue($col($sumCol + $li) . $hr, $lab);
        }
        $sheet->getStyle("A{$hr}:{$lastColL}{$hr}")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$hr}:{$lastColL}{$hr}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E293B');
        $sheet->getStyle("A{$hr}:{$lastColL}{$hr}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $sheet->getRowDimension($hr)->setRowHeight(34);

        // ---------- Data ----------
        $dr = $hr + 1;
        $no = 1;
        foreach ($attempts as $attempt) {
            $answers = $attempt->answers->keyBy('question_id');
            $sheet->setCellValue("A{$dr}", $no++);
            $sheet->setCellValue("B{$dr}", $attempt->student->user->name ?? 'Siswa');
            $sheet->setCellValue("C{$dr}", $kelas);
            foreach ($questions as $qi => $q) {
                $c = $col($firstQ + $qi);
                $a = $answers->get($q->id);
                $earned = $a && $a->earned_score !== null ? (float) $a->earned_score : 0;
                $sheet->setCellValue("{$c}{$dr}", $earned);
                $argb = null;
                if ($q->type === 'mc') {
                    if ($a && $a->is_correct) $argb = 'FFDCFCE7';           // hijau: benar
                    elseif ($a && $a->selected_option_id) $argb = 'FFFEE2E2'; // merah: salah
                    else $argb = 'FFF1F5F9';                                // abu: kosong
                }
                if ($argb) $sheet->getStyle("{$c}{$dr}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($argb);
            }
            $graded = $attempt->status === 'graded';
            $status = $graded ? (((float) $attempt->final_score) >= $pass ? 'LULUS' : 'TIDAK') : 'Belum dinilai';
            $vals = [
                (int) $attempt->correct_count, (int) $attempt->wrong_count, (int) $attempt->blank_count,
                $num($attempt->mc_score), $num($attempt->essay_score), $num($attempt->final_score), $status,
            ];
            foreach ($vals as $vi => $v) {
                $sheet->setCellValue($col($sumCol + $vi) . $dr, $v);
            }
            // warnai status
            $statusCell = $col($sumCol + 6) . $dr;
            $sheet->getStyle($statusCell)->getFont()->setBold(true);
            if ($graded) {
                $sheet->getStyle($statusCell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()
                    ->setARGB(((float) $attempt->final_score) >= $pass ? 'FFDCFCE7' : 'FFFEE2E2');
            }
            $dr++;
        }
        if ($attempts->isEmpty()) {
            $sheet->mergeCells("A{$dr}:{$lastColL}{$dr}");
            $sheet->setCellValue("A{$dr}", 'Belum ada peserta yang mengerjakan.');
            $sheet->getStyle("A{$dr}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // ---------- Border + ukuran kolom + freeze ----------
        $lastRow = max($dr - 1, $hr);
        $sheet->getStyle("A{$hr}:{$lastColL}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFCBD5E1');
        $sheet->getStyle("A{$hr}:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($col($firstQ) . $hr . ':' . $lastColL . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->getColumnDimension('C')->setWidth(16);
        for ($i = $firstQ; $i < $sumCol; $i++) $sheet->getColumnDimension($col($i))->setWidth(9);
        for ($i = $sumCol; $i <= $totalCols; $i++) $sheet->getColumnDimension($col($i))->setWidth(11);
        $sheet->freezePane('D' . ($hr + 1));

        $writer = new Xlsx($ss);
        $fname = 'Hasil-Ujian_' . Str::slug($exam->title . ' ' . $session->name) . '.xlsx';
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fname, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function notifyResult(ExamAttempt $attempt): void
    {
        try {
            $userId = $attempt->student->user_id ?? null;
            if (!$userId) {
                return;
            }
            Notification::create([
                'user_id' => $userId,
                'title' => 'Nilai Ujian Keluar 🎯',
                'message' => 'Nilai ujian "' . ($attempt->session->exam->title ?? '') . '" sudah keluar: ' . rtrim(rtrim((string) $attempt->final_score, '0'), '.') . '.',
                'type' => 'exam',
                'url' => route('student.exams.index'),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Gagal notif nilai ujian: ' . $e->getMessage());
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
