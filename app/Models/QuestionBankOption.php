<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class QuestionBankOption extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function questionBank()
    {
        return $this->belongsTo(QuestionBank::class);
    }
}
