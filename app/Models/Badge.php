<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\LogsAllActivity;

class Badge extends Model
{
    use HasUuids, LogsAllActivity;

    protected $fillable = [
        'name',
        'description',
        'icon',
        'color'
    ];

    public function students()
    {
        return $this->belongsToMany(Student::class, 'student_badges', 'badge_id', 'student_id')->withTimestamps();
    }
}
