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
    ];

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

    public function hasMc(): bool
    {
        return in_array($this->type, ['mixed', 'mc']);
    }

    public function hasEssay(): bool
    {
        return in_array($this->type, ['mixed', 'essay']);
    }
}
