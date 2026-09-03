<?php

namespace App\Support;

use App\Models\Exam;
use App\Models\Question;
use App\Models\QuestionBank;

/**
 * Menyalin soal ujian ke Bank Soal Bersama.
 *
 * Bank Soal tidak punya form "Tambah Soal" sendiri: isinya tumbuh otomatis dari
 * soal yang dibuat guru saat menyusun ujian (manual maupun impor Excel/Word).
 * Soal yang justru DITARIK dari bank tidak dicerminkan ulang — pemanggil di
 * ExamController@pullBank memang tidak memakai kelas ini.
 */
class BankSoal
{
    /** Kembalikan entri bank (baru atau yang sudah ada), atau null bila dilewati. */
    public static function cerminkan(Question $question, Exam $exam, ?QuestionBank $sumber = null): ?QuestionBank
    {
        $subjectId = $exam->teachingAssignment?->subject_id;
        $schoolId  = $exam->teachingAssignment?->classRoom?->school_id;
        $teks = trim((string) $question->question_text);

        // Tanpa mapel, soal tidak akan pernah ketemu lagi saat ditarik ke ujian lain.
        if (! $subjectId || $teks === '') {
            return null;
        }

        // Kunci anti-ganda: sekolah asal + mapel + tipe + teks soal. Sekolah ikut
        // dihitung supaya soal serupa dari dua sekolah tetap tercatat masing-masing.
        $ada = QuestionBank::where('subject_id', $subjectId)
            ->where('school_id', $schoolId)
            ->where('type', $question->type)
            ->where('question_text', $teks)
            ->first();

        if ($ada) {
            return $ada;
        }

        $bank = QuestionBank::create([
            'subject_id'       => $subjectId,
            'school_id'        => $schoolId,
            // Bila soal ini hasil meminjam dari bank sekolah lain, catat asalnya
            // agar bisa ditampilkan sebagai badge "sumber: <sekolah>".
            'source_school_id' => $sumber?->school_id,
            'source_bank_id'   => $sumber?->id,
            // Ujian asal dipakai untuk mengelompokkan daftar bank; judulnya ikut
            // dipotret agar kelompok tetap bernama walau ujiannya nanti dihapus.
            'source_exam_id'    => $exam->id,
            'source_exam_title' => $exam->title,
            'level'         => self::tingkat($exam),
            'type'          => $question->type,
            'question_text' => $teks,
            'image_path'    => $question->image_path,
            'points'        => $question->points,
            'penalty'       => $question->penalty,
            'created_by'    => auth()->id(),
        ]);

        foreach ($question->options as $opsi) {
            $bank->options()->create([
                'label'       => $opsi->label,
                'option_text' => $opsi->option_text,
                'image_path'  => $opsi->image_path,
                'is_correct'  => $opsi->is_correct,
                'order'       => $opsi->order,
            ]);
        }

        return $bank;
    }

    /** Tingkat kelas (VII..XII) ditebak dari nama ruang kelas ujian; null bila tak terbaca. */
    public static function tingkat(Exam $exam): ?string
    {
        $nama = strtoupper((string) $exam->teachingAssignment?->classRoom?->name);

        return preg_match('/\b(VII|VIII|IX|X|XI|XII)\b/', $nama, $m) ? $m[1] : null;
    }
}
