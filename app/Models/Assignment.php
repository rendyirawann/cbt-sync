<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\LogsAllActivity;

class Assignment extends Model
{
    protected $guarded = [];
    use HasUuids, LogsAllActivity;
    
    public function teachingAssignment()
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    public function submissions()
    {
        return $this->hasMany(AssignmentSubmission::class);
    }
}
