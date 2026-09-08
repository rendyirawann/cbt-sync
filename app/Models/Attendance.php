<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsAllActivity;

class Attendance extends Model
{
    use HasUuids, LogsAllActivity;

    protected $fillable = [
        'user_id',
        'type',
        'schedule_id',
        'status',
        'late_minutes',
        'notes',
        'attended_at'
    ];

    protected $casts = [
        'attended_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }
}
