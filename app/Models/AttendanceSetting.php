<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSetting extends Model
{
    protected $fillable = [
        'arrival_start',
        'arrival_end',
        'departure_start'
    ];
}
