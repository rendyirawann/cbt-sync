<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\LogsAllActivity;

class Book extends Model
{
    use HasUuids, LogsAllActivity;

    protected $guarded = [];

    public function borrowings()
    {
        return $this->hasMany(BookBorrowing::class);
    }
}
