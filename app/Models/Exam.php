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
        if ($this->points_mode === 'equal') {
            return $this->normalize ? 100 : (float) $this->questions->count();
        }
        return (float) $this->questions->sum('points');
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
