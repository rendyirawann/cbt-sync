<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Traits\LogsAllActivity;
use Carbon\Carbon;

class ExamSession extends Model
{
    use HasUuids, LogsAllActivity;

    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'show_result' => 'boolean',
        'is_active' => 'boolean',
        'is_makeup' => 'boolean',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_room_id');
    }

    /** Siswa yang ditambahkan manual (lintas kelas). */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'exam_session_student');
    }

    public function attempts()
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /**
     * Daftar siswa yang berhak ikut sesi ini:
     * gabungan siswa kelas (jika class_room_id diisi) + daftar manual.
     */
    /** Tahun ajaran ujian ini, diambil dari penugasan guru pemiliknya. */
    public function academicYearId(): ?string
    {
        return $this->exam?->teachingAssignment?->academic_year_id;
    }

    public function eligibleStudents()
    {
        $manual = $this->students()->with('user')->get();

        if ($this->class_room_id) {
            // Keanggotaan kelas berlaku PER TAHUN AJARAN. Tanpa saringan ini, siswa
            // yang sudah naik kelas tetap terhitung anggota kelas lamanya dan ikut
            // muncul sebagai peserta ujian tahun berikutnya.
            $tahun = $this->academicYearId();
            $classStudentIds = ClassStudent::where('class_room_id', $this->class_room_id)
                ->when($tahun, fn ($q) => $q->where('academic_year_id', $tahun))
                ->pluck('student_id');
            $classStudents = Student::with('user')->whereIn('id', $classStudentIds)->get();
            return $classStudents->concat($manual)->unique('id')->values();
        }

        return $manual->unique('id')->values();
    }

    /** Apakah sudah ada siswa yang memulai (attempt) pada sesi ini. */
    public function hasStartedAttempts(): bool
    {
        return $this->attempts()->exists();
    }

    public function isWithinSchedule(?Carbon $now = null): bool
    {
        $now = $now ?: Carbon::now();
        return (bool) $this->is_active
            && $now->betweenIncluded($this->starts_at, $this->ends_at);
    }

    public function isUpcoming(?Carbon $now = null): bool
    {
        $now = $now ?: Carbon::now();
        return $now->lt($this->starts_at);
    }

    public function isFinished(?Carbon $now = null): bool
    {
        $now = $now ?: Carbon::now();
        return $this->status === 'closed' || $now->gt($this->ends_at);
    }
}
