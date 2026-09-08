<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\LogsAllActivity;

class Exam extends Model
{
    use HasUuids, LogsAllActivity;

    protected $guarded = [];

    protected $casts = [
        'normalize' => 'boolean',
        'wrong_penalty' => 'decimal:2',
        'pass_score' => 'decimal:2',
        'finished_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /** Ujian masih dikerjakan siswa (status "Available"). */
    public function isTersedia(): bool
    {
        return $this->status === \App\Support\SiklusUjian::TERSEDIA;
    }

    /** Sudah dinyatakan selesai otomatis, belum diarsipkan. */
    public function isSelesai(): bool
    {
        return $this->status === \App\Support\SiklusUjian::SELESAI;
    }

    /** Sudah diarsipkan Superadmin/Developer. */
    public function isRiwayat(): bool
    {
        return $this->status === \App\Support\SiklusUjian::RIWAYAT;
    }

    public function teachingAssignment()
    {
        return $this->belongsTo(TeachingAssignment::class, 'teaching_assignment_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class)->orderBy('order')->orderBy('created_at');
    }

    public function sessions()
    {
        return $this->hasMany(ExamSession::class)->latest('starts_at');
    }

    /** Total skor maksimal seluruh soal. */
    public function maxPoints(): float
    {
        return $this->mcMaxPoints() + $this->essayMaxPoints();
    }

    /**
     * Skor maksimal satu bagian ('mc' atau 'essay') = 100 bila bagian itu ada soalnya.
     * Tiap bagian memang dirancang berskala 0–100: PG dibagi rata (100 ÷ jumlah soal PG),
     * sedangkan essay bertotal 100 (dibagi rata pada mode auto, atau dibagi guru pada
     * mode manual dengan batas total 100).
     */
    private function sectionMaxPoints(string $type): float
    {
        return $this->questions->where('type', $type)->isNotEmpty() ? 100.0 : 0.0;
    }

    /** Skor maksimal bagian Pilihan Ganda. */
    public function mcMaxPoints(): float
    {
        return $this->sectionMaxPoints('mc');
    }

    /** Skor maksimal bagian Essay. */
    public function essayMaxPoints(): float
    {
        return $this->sectionMaxPoints('essay');
    }

    /**
     * Bobot tiap bagian (%) pada nilai akhir. Ujian campuran selalu 50 : 50
     * (nilai akhir = rata-rata nilai PG dan nilai Essay). Bila hanya ada satu
     * jenis soal, bagian itu menjadi 100% agar nilai tetap berskala 0–100.
     */
    public function sectionWeights(): array
    {
        $mcAda = $this->mcMaxPoints() > 0;
        $esAda = $this->essayMaxPoints() > 0;

        if ($mcAda && $esAda) {
            return ['mc' => 50, 'essay' => 50];
        }
        if ($mcAda) {
            return ['mc' => 100, 'essay' => 0];
        }
        if ($esAda) {
            return ['mc' => 0, 'essay' => 100];
        }
        return ['mc' => 0, 'essay' => 0];
    }

    /**
     * Apakah sudah ada siswa yang MEMULAI ujian (attempt) di sesi mana pun.
     * Begitu true → ujian terkunci: soal tidak bisa diubah & tidak bisa ditarik ke draft.
     */
    public function hasStartedAttempts(): bool
    {
        return ExamAttempt::whereIn('exam_session_id', $this->sessions()->select('id'))->exists();
    }

    /**
     * Total bobot soal satu bagian yang SUDAH diisi guru (mode manual).
     *
     * PG dan Essay punya JATAH TERPISAH, masing-masing 100, karena tiap bagian
     * memang berskala 0–100 dan nilai akhir merata-ratakan keduanya
     * (lihat sectionWeights()). Jadi 100 poin PG + 100 poin essay, bukan 100
     * untuk keduanya.
     *
     * Hanya soal yang bobotnya ditandai points_set yang dihitung; soal yang
     * belum diatur guru masih memakai bagi rata dan tidak memakan jatah.
     *
     * $kecuali dipakai saat MENGEDIT satu soal: bobot lama soal itu tidak ikut
     * dihitung, kalau tidak guru tidak akan pernah bisa menyimpan nilai yang sama.
     */
    public function totalBobot(string $tipe, ?string $kecuali = null): float
    {
        return (float) $this->questions()
            ->where('type', $tipe)
            ->where('points_set', true)
            ->when($kecuali, fn ($q) => $q->where('id', '!=', $kecuali))
            ->sum('points');
    }

    /** Sisa jatah bobot satu bagian (100 - yang sudah terpakai). */
    public function sisaBobot(string $tipe, ?string $kecuali = null): float
    {
        return round(100 - $this->totalBobot($tipe, $kecuali), 2);
    }

    /** Pintasan lama untuk bagian essay (dipakai tampilan). */
    public function totalBobotEssay(?string $kecuali = null): float
    {
        return $this->totalBobot('essay', $kecuali);
    }

    public function sisaBobotEssay(?string $kecuali = null): float
    {
        return $this->sisaBobot('essay', $kecuali);
    }

    /**
     * Entri Bank Soal yang lahir dari ujian ini (dicerminkan otomatis saat guru
     * membuat soal — lihat App\Support\BankSoal). Dipakai untuk menghitung
     * dampak sebelum ujian dihapus.
     */
    public function bankQuestions()
    {
        return $this->hasMany(QuestionBank::class, 'source_exam_id');
    }

    public function hasMc(): bool
    {
        return in_array($this->type, ['mixed', 'mc']);
    }

    public function hasEssay(): bool
    {
        return in_array($this->type, ['mixed', 'essay']);
    }
}
