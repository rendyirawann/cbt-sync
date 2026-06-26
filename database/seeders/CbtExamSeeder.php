<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\ClassStudent;
use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\ExamSession;
use Carbon\Carbon;

/**
 * Seeder demo CBT:
 *  1. Ujian Pilihan Ganda (30 soal) — 2 sesi.
 *  2. Ujian Pilihan Ganda + Essay (10 PG + 3 Essay) — 3 sesi.
 *
 * Sesi diarahkan ke kelas yang memiliki siswa agar langsung bisa diuji
 * (mis. akun demo siswa@lms.com). Idempotent: menghapus exam berjudul sama
 * sebelum membuat ulang.
 *
 * Jalankan: php artisan db:seed --class=CbtExamSeeder
 */
class CbtExamSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tentukan guru (akun demo, fallback guru pertama).
        $teacher = User::where('email', 'guru@lms.com')->first()?->teacher ?? Teacher::first();
        if (!$teacher) {
            $this->command->error('Tidak ada data guru. Jalankan DemoAccountSeeder dulu.');
            return;
        }

        $tas = TeachingAssignment::with(['subject', 'classRoom'])
            ->where('teacher_id', $teacher->id)->get();
        if ($tas->isEmpty()) {
            $this->command->error('Guru belum punya penugasan mengajar (teaching_assignments).');
            return;
        }

        // Pilih TA yang kelasnya punya siswa terbanyak → agar sesi langsung bisa diikuti.
        $taPg = $tas->sortByDesc(fn ($ta) => ClassStudent::where('class_room_id', $ta->class_room_id)->count())->first();
        $taMix = $tas->firstWhere('id', '!=', $taPg->id) ?? $taPg;

        $studentClassId = $taPg->class_room_id;                 // kelas yang punya siswa
        $studentIds = ClassStudent::where('class_room_id', $studentClassId)->pluck('student_id')->unique()->values()->all();

        // ============================================================
        // EXAM 1 — 30 SOAL PILIHAN GANDA, 2 SESI
        // ============================================================
        $title1 = 'UTS ' . ($taPg->subject->name ?? 'Mapel') . ' — 30 Soal Pilihan Ganda';
        Exam::where('title', $title1)->get()->each->delete();

        $exam1 = Exam::create([
            'teaching_assignment_id' => $taPg->id,
            'title' => $title1,
            'description' => 'Ujian Tengah Semester — 30 soal pilihan ganda. Nilai dibagi rata otomatis.',
            'type' => 'mc',
            'points_mode' => 'equal',     // 100 / 30 per soal
            'wrong_penalty' => 0,
            'normalize' => true,
            'pass_score' => 75,
            'status' => 'published',
        ]);

        $ops = ['+', '−', '×'];
        for ($i = 1; $i <= 30; $i++) {
            $op = $ops[$i % 3];
            $a = 11 + $i;
            $b = 2 + ($i % 8);
            $correct = $op === '+' ? $a + $b : ($op === '−' ? $a - $b : $a * $b);

            $options = array_values(array_unique([$correct, $correct + 1, $correct - 1, $correct + 2]));
            // jaga tetap 4 opsi
            while (count($options) < 4) { $options[] = $correct + count($options) + 2; }
            shuffle($options);
            $correctIdx = array_search($correct, $options, true);

            $this->mc($exam1, $i, "Hasil dari {$a} {$op} {$b} = ?", array_map('strval', $options), $correctIdx, 1);
        }

        $this->session($exam1, 'Sesi 1 — Sedang Berlangsung', $studentClassId,
            Carbon::now()->subMinutes(30), Carbon::now()->addHours(3), 90, 40);
        $this->session($exam1, 'Sesi 2 — Terjadwal (Besok)', $studentClassId,
            Carbon::now()->addDay()->setTime(8, 0), Carbon::now()->addDay()->setTime(11, 0), 90, 40);

        // ============================================================
        // EXAM 2 — PILIHAN GANDA + ESSAY, 3 SESI
        // ============================================================
        $title2 = 'UAS ' . ($taMix->subject->name ?? 'Mapel') . ' — PG + Essay';
        Exam::where('title', $title2)->get()->each->delete();

        $exam2 = Exam::create([
            'teaching_assignment_id' => $taMix->id,
            'title' => $title2,
            'description' => 'Ujian Akhir Semester — 10 pilihan ganda (50) + 3 essay (50). Total 100.',
            'type' => 'mixed',
            'points_mode' => 'per_question',
            'wrong_penalty' => 0,
            'normalize' => true,
            'pass_score' => 70,
            'status' => 'published',
        ]);

        $mcq = [
            ['Kepanjangan dari CPU adalah…', ['Central Processing Unit', 'Computer Personal Unit', 'Central Program Utility', 'Control Process Unit'], 0],
            ['1 byte sama dengan … bit.', ['4', '8', '16', '32'], 1],
            ['Bahasa markah (markup) untuk membuat halaman web adalah…', ['Python', 'HTML', 'C++', 'SQL'], 1],
            ['Berikut yang merupakan perangkat masukan (input) adalah…', ['Monitor', 'Printer', 'Keyboard', 'Speaker'], 2],
            ['Sistem bilangan biner menggunakan basis…', ['2', '8', '10', '16'], 0],
            ['RAM adalah singkatan dari…', ['Read Access Memory', 'Random Access Memory', 'Rapid Active Module', 'Run Access Memory'], 1],
            ['Protokol yang digunakan untuk mengakses halaman web adalah…', ['FTP', 'SMTP', 'HTTP', 'SSH'], 2],
            ['Ekstensi berikut yang merupakan berkas gambar adalah…', ['.docx', '.jpg', '.mp3', '.exe'], 1],
            ['Berikut yang merupakan sistem operasi adalah…', ['Microsoft Word', 'Photoshop', 'Linux', 'Chrome'], 2],
            ['Kombinasi tombol untuk menyalin (copy) adalah…', ['Ctrl + V', 'Ctrl + X', 'Ctrl + C', 'Ctrl + Z'], 2],
        ];
        $order = 1;
        foreach ($mcq as $q) {
            $this->mc($exam2, $order++, $q[0], $q[1], $q[2], 5);   // 10 × 5 = 50
        }

        $this->essay($exam2, $order++, 'Jelaskan perbedaan antara RAM dan ROM beserta contoh penggunaannya.', 20);
        $this->essay($exam2, $order++, 'Sebutkan dan jelaskan 3 perangkat masukan (input) beserta fungsinya.', 15);
        $this->essay($exam2, $order++, 'Menurut pendapatmu, mengapa keamanan data penting di era digital? Jelaskan.', 15);

        // 3 sesi: 2 berbasis kelas (kelas yang ada siswanya) + 1 manual (pilih siswa).
        $this->session($exam2, 'Sesi 1 — Sedang Berlangsung', $studentClassId,
            Carbon::now()->subMinutes(15), Carbon::now()->addHours(2), 60, 40);
        $this->session($exam2, 'Sesi 2 — Terjadwal (Lusa)', $studentClassId,
            Carbon::now()->addDays(2)->setTime(9, 0), Carbon::now()->addDays(2)->setTime(11, 30), 60, 40);
        $this->session($exam2, 'Sesi 3 — Peserta Pilihan (Manual)', null,
            Carbon::now()->subMinutes(10), Carbon::now()->addHours(2), 60, 30, $studentIds);

        $this->command->info('CBT seeder selesai:');
        $this->command->info("  • {$title1} (30 PG, 2 sesi)");
        $this->command->info("  • {$title2} (10 PG + 3 Essay, 3 sesi)");
        $this->command->info('  Sesi diarahkan ke kelas: ' . ($taPg->classRoom->name ?? $studentClassId) . " ({$tas->count()} TA guru terdeteksi).");
    }

    /** Buat soal pilihan ganda + opsinya (label A,B,C,…). */
    private function mc(Exam $exam, int $order, string $text, array $optTexts, int $correctIdx, float $points, float $penalty = 0): void
    {
        $q = Question::create([
            'exam_id' => $exam->id,
            'type' => 'mc',
            'question_text' => $text,
            'points' => $points,
            'penalty' => $penalty,
            'order' => $order,
        ]);

        foreach (array_values($optTexts) as $i => $t) {
            QuestionOption::create([
                'question_id' => $q->id,
                'label' => chr(65 + $i),
                'option_text' => $t,
                'is_correct' => $i === $correctIdx,
                'order' => $i,
            ]);
        }
    }

    private function essay(Exam $exam, int $order, string $text, float $points): void
    {
        Question::create([
            'exam_id' => $exam->id,
            'type' => 'essay',
            'question_text' => $text,
            'points' => $points,
            'order' => $order,
        ]);
    }

    /**
     * Buat sesi ujian. Jika $classId null & $manualStudentIds berisi → sesi
     * dengan daftar peserta manual.
     */
    private function session(Exam $exam, string $name, ?string $classId, Carbon $start, Carbon $end, int $duration, ?int $capacity = null, array $manualStudentIds = []): void
    {
        $s = ExamSession::create([
            'exam_id' => $exam->id,
            'name' => $name,
            'class_room_id' => $classId,
            'starts_at' => $start,
            'ends_at' => $end,
            'duration_minutes' => $duration,
            'max_capacity' => $capacity,
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'show_result' => true,
            'status' => 'scheduled',
            'is_active' => true,
        ]);

        if ($classId === null && !empty($manualStudentIds)) {
            $s->students()->sync($manualStudentIds);
        }
    }
}
