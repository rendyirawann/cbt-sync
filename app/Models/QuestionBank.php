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

    public function options()
    {
        return $this->hasMany(QuestionBankOption::class)->orderBy('order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
