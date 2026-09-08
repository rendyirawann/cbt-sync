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
        $this->authorizeHasil($session->exam);

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
        $this->authorizeHasil($attempt->session->exam);

        $exam = $attempt->session->exam;
        $answers = $attempt->answers->keyBy('question_id');

        // Guru hanya melihat & menilai soal yang BENAR-BENAR diterima siswa ini
        // (paket bisa berbeda tiap siswa bila guru memakai pemilihan otomatis),
        // dan bobotnya pun dihitung di dalam paket itu.
        $questions = CbtScoringService::paketSoal($attempt)->sortBy('order')->values();
        $weights = [];
        foreach ($questions as $q) {
            $weights[$q->id] = CbtScoringService::bobotDalamPaket($questions, $exam, $q);
        }

        return view('backend.master.exams.grade', compact('attempt', 'exam', 'answers', 'weights', 'questions'));
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
        $feedbacks = $request->input('feedback', []);      // [question_id => catatan]
        $flags = $request->input('essay_correct', []);      // [question_id => '1'|'0']
        $bobotKirim = $request->input('bobot', []);         // [question_id => bobot] (mode manual)
        $manual = $exam->points_mode !== 'auto';

        // Hanya soal dalam paket siswa ini (pemilihan acak membuat tiap siswa bisa
        // menerima soal berbeda).
        $paket = CbtScoringService::paketSoal($attempt);
        $essay = $paket->where('type', 'essay');

        $nomor = [];
        foreach ($paket->values() as $i => $q) {
            $nomor[$q->id] = $i + 1;
        }
        $f = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');

        // ------------------------------------------------------------------
        // MODE MANUAL: bobot tiap soal ditentukan guru DI SINI, saat memeriksa
        // (PG maupun Essay), lalu disimpan ke soalnya. Jatah tiap bagian 100
        // poin, karena nilai akhir merata-ratakan dua bagian berskala 0–100.
        // ------------------------------------------------------------------
        if ($manual) {
            $bobotBaru = [];
            $takValid = [];
            $totalPerBagian = ['mc' => 0.0, 'essay' => 0.0];

            foreach ($paket as $q) {
                $b = $bobotKirim[$q->id] ?? null;
                if ($b === null || $b === '' || (float) $b <= 0 || (float) $b > 100) {
                    $takValid[] = 'Soal ' . ($nomor[$q->id] ?? '?');
                    continue;
                }
                $bobotBaru[$q->id] = round((float) $b, 2);
                $totalPerBagian[$q->type] += $bobotBaru[$q->id];
            }

            if ($takValid) {
                return redirect()->back()->withInput()->with('error',
                    'Bobot belum diisi / tidak wajar pada: ' . implode(', ', $takValid)
                    . '. Bobot harus lebih dari 0 dan maksimal 100.');
            }

            // Total wajib tepat 100 per bagian. Hanya ditegakkan bila siswa menerima
            // SELURUH soal; pada pemilihan acak paket seseorang memang tidak mungkin
            // berjumlah 100 dan penyekalaan ditangani CbtScoringService.
            if (($exam->question_selection ?? 'all') === 'all') {
                foreach (['mc' => 'Pilihan Ganda', 'essay' => 'Essay'] as $tp => $nama) {
                    if ($paket->where('type', $tp)->isEmpty()) {
                        continue;
                    }
                    $tot = $totalPerBagian[$tp];
                    if (abs($tot - 100) > 0.01) {
                        return redirect()->back()->withInput()->with('error',
                            'Total bobot soal ' . $nama . ' = ' . $f($tot) . ', seharusnya tepat 100 ('
                            . ($tot < 100 ? 'tambah' : 'kurangi') . ' ' . $f(abs($tot - 100)) . ' poin).');
                    }
                }
            }

            // Disimpan ke soal supaya konsisten untuk siswa lain & saat dibuka lagi.
            $bobotBerubah = false;
            foreach ($paket as $q) {
                $baru = $bobotBaru[$q->id];
                if (!$q->points_set || abs((float) $q->points - $baru) > 0.001) {
                    $q->update(['points' => $baru, 'points_set' => true]);
                    $bobotBerubah = true;
                }
            }
        }

        // Essay: tiap soal wajib ditandai Benar/Salah. Menyimpan dengan sebagian
        // belum ditandai dulu memfinalkan nilai dengan essay dihitung 0 — nilai
        // akhir jadi jauh lebih kecil tanpa guru menyadarinya.
        $belumDitandai = [];
        foreach ($essay as $q) {
            if (!isset($flags[$q->id]) || $flags[$q->id] === '') {
                $belumDitandai[] = 'Soal ' . ($nomor[$q->id] ?? '?');
            }
        }
        if ($belumDitandai) {
            return redirect()->back()->withInput()->with('error',
                'Masih ada soal essay yang belum ditandai Benar/Salah: ' . implode(', ', $belumDitandai) . '.');
        }

        // Bobot dibaca ULANG setelah kemungkinan diperbarui di atas.
        $paket = CbtScoringService::paketSoal($attempt->fresh());
        foreach ($paket->where('type', 'essay') as $q) {
            $w = CbtScoringService::bobotDalamPaket($paket, $exam->fresh(), $q);
            $val = (($flags[$q->id] ?? '0') == '1') ? $w : 0.0;   // Benar -> bobot penuh, Salah -> 0

            ExamAnswer::updateOrCreate(
                ['exam_attempt_id' => $attempt->id, 'question_id' => $q->id],
                ['earned_score' => round($val, 2), 'feedback' => $feedbacks[$q->id] ?? null, 'graded' => true]
            );
        }

        // PG dikoreksi ulang: bobotnya bisa saja baru diubah guru di layar ini.
        CbtScoringService::gradeMc($attempt->fresh());

        // Nilai akhir SELALU dihitung sistem untuk kedua mode:
        // (Nilai PG 0–100 + Nilai Essay 0–100) / 2, sesuai keterangan di layar.
        CbtScoringService::recomputeAfterEssayGrading($attempt->fresh());

        // Bobot itu milik SOAL, bukan milik satu siswa. Kalau guru mengubahnya,
        // nilai siswa lain yang sudah dinilai ikut dihitung ulang — kalau tidak,
        // dua siswa pada ujian yang sama dinilai dengan bobot berbeda.
        if ($manual && !empty($bobotBerubah)) {
            $lain = ExamAttempt::whereIn('exam_session_id', $exam->sessions()->select('id'))
                ->where('id', '!=', $attempt->id)
                ->whereIn('status', ['graded'])
                ->get();
            foreach ($lain as $a) {
                CbtScoringService::gradeMc($a);
                CbtScoringService::computeFinal($a->fresh());
            }
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

    /**
     * Hasil & nilai mengikuti aturan siklus ujian: pada ujian berstatus SELESAI
     * hanya Superadmin/Developer yang boleh membukanya, dan pada ujian HISTORY
     * tab hasilnya disembunyikan dari Admin & Guru — halaman ini pun ditutup
     * supaya tidak bisa dicapai lewat URL langsung.
     */
    private function authorizeHasil(Exam $exam): void
    {
        abort_unless(\App\Support\SiklusUjian::bolehLihat($exam), 404);
        abort_unless(\App\Support\SiklusUjian::bolehLihatHasil($exam), 403,
            'Ujian ini sudah diarsipkan sebagai History. Hasil & nilainya hanya dapat dibuka Superadmin dan Developer.');
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
