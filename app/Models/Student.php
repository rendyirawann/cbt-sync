<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Student extends Model
{
    protected $guarded = [];
    use HasUuids;
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classStudents()
    {
        return $this->hasMany(ClassStudent::class);
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'student_badges', 'student_id', 'badge_id')->withTimestamps();
    }

    public function studentBadges()
    {
        return $this->hasMany(StudentBadge::class);
    }

    public function moduleViews()
    {
        return $this->hasMany(ModuleView::class);
    }

    public function bookBorrowings()
    {
        return $this->hasMany(BookBorrowing::class);
    }
}
