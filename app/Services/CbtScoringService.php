<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Question;

/**
 * Mesin penilaian CBT.
 *  - PG (mc) dikoreksi otomatis saat submit.
 *  - Essay dinilai manual oleh guru, lalu nilai akhir dihitung ulang (akumulasi 2 tahap).
 *  - Mode nilai: per_question (pakai points tiap soal), equal (bagi rata),
 *    manual (guru menentukan nilai akhir).
 */
class CbtScoringService
{
    /**
     * Bobot (skor maksimal) sebuah soal. Guru TIDAK perlu mengisi poin apa pun —
     * sistem selalu membagi rata di dalam bagiannya sehingga tiap bagian bertotal 100.
     *
     *  - Pilihan Ganda (kedua mode) : 100 ÷ jumlah soal PG    (mis. 13 soal → 7,69/soal)
     *  - Essay mode auto            : 100 ÷ jumlah soal essay (mis. 7 soal → 14,29/soal)
     *  - Essay mode manual          : batas per soal 100, karena guru bebas menentukan
     *                                 nilai tiap essay; yang dibatasi adalah TOTAL essay
     *                                 (≤ 100, divalidasi saat menyimpan penilaian).
     *
     * Parameter $count dipertahankan agar pemanggil lama tetap jalan, tapi tidak dipakai.
     */
    public static function questionWeight(Exam $exam, Question $question, int $count = 0): float
    {
        $n = $exam->questions->where('type', $question->type)->count();
        if ($question->type === 'essay' && $exam->points_mode !== 'auto') {
            // Mode manual: bobot tiap essay ditentukan guru saat menilai (disimpan di kolom
            // points, total seluruh essay wajib 100). Sebelum diisi, tampilkan bagi rata.
            $set = (float) $question->points;
            return $set > 1 ? $set : ($n > 0 ? 100 / $n : 0.0);
        }
        return $n > 0 ? 100 / $n : 0.0;
    }

    /** Pengurang nilai untuk jawaban PG yang salah (mode auto tidak memakai pengurang). */
    public static function wrongPenalty(Exam $exam, Question $question): float
    {
        return $exam->points_mode === 'auto' ? 0.0 : (float) $question->penalty;
    }

    /** Koreksi otomatis seluruh jawaban Pilihan Ganda. */
    public static function gradeMc(ExamAttempt $attempt): void
    {
        $exam = $attempt->session->exam->load('questions.options');
        $questions = $exam->questions;
        $count = $questions->count();
        $answers = $attempt->answers()->get()->keyBy('question_id');

        $correct = 0; $wrong = 0; $blank = 0; $mcScore = 0;

        foreach ($questions as $q) {
            if ($q->type !== 'mc') {
                continue;
            }
            $weight = self::questionWeight($exam, $q, $count);
            $ans = $answers->get($q->id);

            if (!$ans || !$ans->selected_option_id) {
                $blank++;
                if ($ans) {
                    $ans->update(['is_correct' => false, 'earned_score' => 0, 'graded' => true]);
                }
                continue;
            }

            $opt = $q->options->firstWhere('id', $ans->selected_option_id);
            $isCorrect = $opt ? (bool) $opt->is_correct : false;

            if ($isCorrect) {
                $correct++;
                $earned = $weight;
            } else {
                $wrong++;
                $earned = -self::wrongPenalty($exam, $q);
            }

            $mcScore += $earned;
            $ans->update([
                'is_correct'   => $isCorrect,
                'earned_score' => round($earned, 2),
                'graded'       => true,
            ]);
        }

        $attempt->mc_score = round($mcScore, 2);
        $attempt->correct_count = $correct;
        $attempt->wrong_count = $wrong;
        $attempt->blank_count = $blank;
        $attempt->save();
    }

    /**
     * Dipanggil saat siswa submit. Koreksi PG; bila ada essay atau mode manual,
     * status jadi "submitted" (menunggu guru). Jika PG-saja & otomatis → langsung "graded".
     */
    public static function finalizeOnSubmit(ExamAttempt $attempt): void
    {
        self::gradeMc($attempt);

        $exam = $attempt->session->exam;
        $hasEssay = $exam->questions->where('type', 'essay')->count() > 0;

        if ($hasEssay) {
            // Masih menunggu guru menilai essay.
            $attempt->status = 'submitted';
            $attempt->essay_graded = false;
            $attempt->save();
            return;
        }

        self::computeFinal($attempt);
    }

    /**
     * Nilai akhir untuk KEDUA mode (manual & auto):
     * bagian PG dan bagian Essay masing-masing dijadikan skala 0–100, lalu dirata-ratakan.
     *
     *   Nilai PG    = poin PG diperoleh    ÷ total poin PG    × 100
     *   Nilai Essay = poin Essay diperoleh ÷ total poin Essay × 100
     *   Nilai Akhir = (Nilai PG + Nilai Essay) ÷ 2
     *
     * Bila ujian hanya berisi satu jenis soal, bagian itu menjadi 100% nilai akhir
     * (lihat Exam::sectionWeights()). Bedanya mode hanya pada cara menentukan total
     * poin tiap bagian: manual = poin yang diisi guru, auto = dibagi rata otomatis.
     */
    public static function computeFinal(ExamAttempt $attempt): void
    {
        $exam = $attempt->session->exam;

        $essayScore = (float) $attempt->answers()
            ->whereHas('question', fn ($q) => $q->where('type', 'essay'))
            ->sum('earned_score');

        $total = (float) $attempt->mc_score + $essayScore;
        if ($total < 0) {
            $total = 0;
        }

        $mcMax = $exam->mcMaxPoints();
        $esMax = $exam->essayMaxPoints();
        $mcPct = $mcMax > 0 ? max(0, (float) $attempt->mc_score) / $mcMax * 100 : 0;
        $esPct = $esMax > 0 ? max(0, $essayScore) / $esMax * 100 : 0;
        $w = $exam->sectionWeights();
        $final = round($mcPct * $w['mc'] / 100 + $esPct * $w['essay'] / 100, 2);

        $attempt->essay_score = round($essayScore, 2);
        $attempt->total_score = round($total, 2);
        $attempt->final_score = $final;
        $attempt->essay_graded = true;
        $attempt->status = 'graded';
        $attempt->save();
    }

    /** Setelah guru menilai essay (mode otomatis), hitung ulang nilai akhir. */
    public static function recomputeAfterEssayGrading(ExamAttempt $attempt): void
    {
        self::computeFinal($attempt);
    }
}
