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
    /** Bobot/nilai maksimal sebuah soal sesuai mode penilaian. */
    public static function questionWeight(Exam $exam, Question $question, int $count): float
    {
        if ($exam->points_mode === 'equal') {
            return ($exam->normalize ? 100 : $count) / max($count, 1);
        }
        // per_question & manual memakai nilai per-soal yang diset guru
        return (float) $question->points;
    }

    /** Pengurang nilai untuk jawaban PG yang salah. */
    public static function wrongPenalty(Exam $exam, Question $question): float
    {
        return $exam->points_mode === 'equal'
            ? (float) $exam->wrong_penalty
            : (float) $question->penalty;
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

        if ($exam->points_mode === 'manual' || $hasEssay) {
            $attempt->status = 'submitted';
            $attempt->essay_graded = false;
            $attempt->save();
            return;
        }

        self::computeFinal($attempt);
    }

    /** Hitung nilai akhir = PG (otomatis) + Essay (yang sudah dinilai), lalu normalisasi. */
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

        $max = $exam->maxPoints();
        $final = $exam->normalize
            ? ($max > 0 ? round($total / $max * 100, 2) : 0)
            : round($total, 2);

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
