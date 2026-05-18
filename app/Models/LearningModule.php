<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningModule extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory, \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $fillable = [
        'teaching_assignment_id',
        'title',
        'description',
        'zoom_link',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'is_published'
    ];

    public function teachingAssignment()
    {
        return $this->belongsTo(TeachingAssignment::class);
    }

    public function comments()
    {
        return $this->hasMany(ModuleComment::class)->whereNull('parent_id')->orderBy('created_at', 'desc');
    }

    public function getFormattedFileSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function views()
    {
        return $this->hasMany(ModuleView::class);
    }
}
