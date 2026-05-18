<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ModuleView extends Model
{
    use HasUuids;

    protected $guarded = [];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function learningModule()
    {
        return $this->belongsTo(LearningModule::class);
    }
}
