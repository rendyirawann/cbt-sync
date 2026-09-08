<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\LogsAllActivity;

class ClassRoom extends Model
{
    protected $guarded = [];
    use HasUuids, LogsAllActivity;
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Plotting siswa ke rombel ini (per tahun ajaran).
     *
     * Relasi ini sebelumnya tidak ada sama sekali — satu-satunya relasi di model
     * ini hanya school(). Akibatnya semua kode yang butuh "rombel ini punya
     * siswa berapa" harus bertanya dari arah sebaliknya (ClassStudent) atau
     * memakai subquery mentah.
     */
    public function classStudents()
    {
        return $this->hasMany(ClassStudent::class);
    }

    /** Siswa pada rombel ini, lewat tabel plotting. */
    public function students()
    {
        return $this->hasManyThrough(Student::class, ClassStudent::class, 'class_room_id', 'id', 'id', 'student_id');
    }
}
