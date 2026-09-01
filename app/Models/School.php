<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class School extends Model
{
    protected $guarded = [];
    use HasUuids;
}
