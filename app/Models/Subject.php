<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\LogsAllActivity;

class Subject extends Model
{
    protected $guarded = [];
    use HasUuids, LogsAllActivity;
}
