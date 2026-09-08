<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\LogsAllActivity;

class Question extends Model
{
    use HasUuids, LogsAllActivity;

    protected $guarded = [];

    protected $casts = [
        'points' => 'decimal:2',
        // Penanda "bobot soal ini diisi guru" (mode penilaian manual). Bukan angka:
        // sebelum ada kolom ini, bobot yang diisi guru ditebak dari points > 1, dan
        // tebakan itu tidak bisa membedakan bobot 1 poin yang disengaja dari nilai
        // bawaan points = 1 pada semua soal lama.
        'points_set' => 'boolean',
        'penalty' => 'decimal:2',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order');
    }

    public function correctOption()
    {
        return $this->hasOne(QuestionOption::class)->where('is_correct', true);
    }
}
