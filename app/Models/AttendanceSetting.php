<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsAllActivity;

class AttendanceSetting extends Model
{
    use LogsAllActivity;

    protected $fillable = [
        'arrival_start',
        'arrival_end',
        'departure_start'
    ];
}
