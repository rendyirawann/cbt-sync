<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ExamAnswer extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'is_correct' => 'boolean',
        'graded' => 'boolean',
        'earned_score' => 'decimal:2',
        'answer_images' => 'array',
    ];

    public function attempt()
    {
        return $this->belongsTo(ExamAttempt::class, 'exam_attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function selectedOption()
    {
        return $this->belongsTo(QuestionOption::class, 'selected_option_id');
    }
}
