<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\LogsAllActivity;

class AcademicYear extends Model
{
    use LogsAllActivity;

    protected $guarded = [];
    use HasUuids;
}
