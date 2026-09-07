<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\LogsAllActivity;

class QuestionBank extends Model
{
    use HasUuids, LogsAllActivity;

    protected $guarded = [];

    protected $casts = [
        'points' => 'decimal:2',
        'penalty' => 'decimal:2',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    /** Sekolah asal soal ini (untuk filter "soal buatan sekolah X"). */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /** Sekolah asal soal bila entri ini hasil meminjam dari bank sekolah lain. */
    public function sourceSchool()
    {
        return $this->belongsTo(School::class, 'source_school_id');
    }

    /** Ujian tempat soal ini pertama dibuat (dasar pengelompokan di halaman bank). */
    public function sourceExam()
    {
        return $this->belongsTo(Exam::class, 'source_exam_id');
    }

    public function options()
    {
        return $this->hasMany(QuestionBankOption::class)->orderBy('order');
    }

    /**
     * Gerbang kebocoran soal antar sekolah.
     *
     * Bank Soal memang lintas sekolah, TAPI soal ujian yang masih berjalan tidak
     * boleh terbaca sekolah lain: bila dua sekolah mengikuti asesmen yang sama,
     * guru sekolah B bisa melihat soal sekolah A sebelum ujiannya dilaksanakan.
     *
     * Aturannya:
     *   • soal sekolah SENDIRI  → selalu terlihat (bank internal tetap utuh);
     *   • soal sekolah LAIN     → hanya bila ujian asalnya sudah Selesai
     *                             (finished) atau diarsipkan (history);
     *   • source_exam_id NULL   → dianggap boleh, karena ujian asalnya sudah
     *                             tidak ada di sistem sehingga mustahil sedang
     *                             berlangsung (judulnya tetap terpotret di
     *                             source_exam_title);
     *   • Superadmin & Developer melihat semuanya, sejalan dengan hak mereka
     *     pada ujian berstatus Selesai.
     *
     * Dipakai bersama oleh halaman Bank Soal dan modal "Tarik dari Bank Soal"
     * supaya aturannya tidak bisa berbeda di dua tempat.
     */
    public function scopeTerlihatOleh($query, ?string $schoolId)
    {
        if (\App\Support\SiklusUjian::pengawas()) {
            return $query;
        }

        return $query->where(function ($q) use ($schoolId) {
            if ($schoolId) {
                $q->where('school_id', $schoolId);
            }

            $q->orWhereNull('source_exam_id')
              ->orWhereHas('sourceExam', fn ($e) => $e->whereIn('status', [
                  \App\Support\SiklusUjian::SELESAI,
                  \App\Support\SiklusUjian::RIWAYAT,
              ]));
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
