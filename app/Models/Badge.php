<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Badge extends Model
{
    use HasUuids;

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
