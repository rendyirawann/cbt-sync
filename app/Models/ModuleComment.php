<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuleComment extends Model
{
    use HasUuids;

    protected $fillable = [
        'learning_module_id',
        'user_id',
        'parent_id',
        'comment'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function learningModule(): BelongsTo
    {
        return $this->belongsTo(LearningModule::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ModuleComment::class, 'parent_id');
    }
}
