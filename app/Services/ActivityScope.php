<?php

namespace App\Services;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\ClassRoom;
use App\Models\TeachingAssignment;

/**
 * Menentukan cakupan Log Activity per peran (berjenjang):
 *  - Superadmin : semua log (null = tanpa filter)
 *  - Admin      : semua user di sekolahnya (users.school_id)
 *  - Guru       : dirinya + siswa yang diajarnya (via kelas pada teaching_assignment)
 *  - Siswa/lain : hanya aktivitasnya sendiri
 */
class ActivityScope
{
    /** Daftar causer_id (user id) yang boleh dilihat; null berarti tanpa filter (Superadmin). */
    public function visibleCauserIds($user): ?array
    {
        if (!$user) {
            return [];
        }

        if ($user->hasRole(['Superadmin', 'superadmin'])) {
            return null;
        }

        if ($user->hasRole(['Admin', 'admin'])) {
            $schoolId = $user->school_id ?? null;
            return $schoolId ? $this->schoolUserIds($schoolId) : [$user->id];
        }

        if ($user->hasRole('Guru') && $user->teacher) {
            $classIds = TeachingAssignment::where('teacher_id', $user->teacher->id)->pluck('class_room_id');
            $ids = Student::whereHas('classStudents', fn ($q) => $q->whereIn('class_room_id', $classIds))
                ->pluck('user_id')->filter()->all();
            $ids[] = $user->id;
            return array_values(array_unique($ids));
        }

        // Siswa & peran lain: hanya aktivitas sendiri.
        return [$user->id];
    }

    /** Semua user_id yang tergabung dalam satu sekolah (admin/user, siswa, dan guru pengampu kelas). */
    private function schoolUserIds($schoolId): array
    {
        $ids = User::where('school_id', $schoolId)->pluck('id')->all();

        $ids = array_merge($ids, Student::where('school_id', $schoolId)->pluck('user_id')->filter()->all());

        $classIds = ClassRoom::where('school_id', $schoolId)->pluck('id');
        $teacherIds = TeachingAssignment::whereIn('class_room_id', $classIds)->pluck('teacher_id')->unique();
        $ids = array_merge($ids, Teacher::whereIn('id', $teacherIds)->pluck('user_id')->filter()->all());

        return array_values(array_unique(array_filter($ids)));
    }
}
