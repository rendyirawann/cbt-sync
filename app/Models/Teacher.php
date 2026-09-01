<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\LogsAllActivity;

class Teacher extends Model
{
    protected $guarded = [];
    use HasUuids, LogsAllActivity;
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function teachingAssignments()
    {
        return $this->hasMany(TeachingAssignment::class, 'teacher_id');
    }
}
