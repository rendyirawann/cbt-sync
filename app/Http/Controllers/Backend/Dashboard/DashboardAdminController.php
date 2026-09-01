<?php

namespace App\Http\Controllers\Backend\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\TeachingAssignment;
use App\Models\LearningModule;
use App\Models\ClassStudent;

class DashboardAdminController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Siswa tidak boleh berada di dashboard admin — arahkan ke portal mereka.
        if ($user->hasRole('Siswa')) {
            return redirect()->route('student.dashboard');
        }

        if ($user->hasAnyRole(['Superadmin', 'Admin', 'admin'])) {
            $stats = [
                'type' => 'admin',
                'schools' => School::count(),
                'teachers' => Teacher::count(),
                'students' => Student::count(),
                'classes' => ClassRoom::count(),
            ];

            $recentData = TeachingAssignment::with(['teacher.user', 'classRoom', 'subject'])
                ->latest()
                ->take(5)
                ->get();
        } elseif ($user->hasRole('Guru')) {
            if (!$user->teacher) {
                return view('backend.dashboard.index', [
                    'stats' => ['type' => 'guru'],
                    'recentData' => collect(),
                ])->with('error', 'Profil guru belum lengkap. Hubungi administrator.');
            }
            $teacherId = $user->teacher->id;
            $stats = [
                'type' => 'guru',
                'my_classes' => TeachingAssignment::where('teacher_id', $teacherId)->distinct('class_room_id')->count(),
                'my_subjects' => TeachingAssignment::where('teacher_id', $teacherId)->distinct('subject_id')->count(),
                'my_modules' => LearningModule::whereHas('teachingAssignment', function($q) use ($teacherId) {
                    $q->where('teacher_id', $teacherId);
                })->count(),
                'my_students' => ClassStudent::whereIn('class_room_id', function($q) use ($teacherId) {
                    $q->select('class_room_id')->from('teaching_assignments')->where('teacher_id', $teacherId);
                })->count(),
            ];

            $recentData = LearningModule::whereHas('teachingAssignment', function($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId);
            })->with(['teachingAssignment.subject', 'teachingAssignment.classRoom'])->latest()->take(5)->get();
        } else {
            // User tanpa role yang dikenali (fallback aman).
            $student = $user->student;
            if (!$student) {
                return view('backend.dashboard.index', [
                    'stats' => ['type' => 'unknown'],
                    'recentData' => collect(),
                ]);
            }
            $classId = ClassStudent::where('student_id', $student->id)
                ->whereHas('academicYear', function($q) { $q->where('is_active', 1); })
                ->value('class_room_id');

            $stats = [
                'type' => 'siswa',
                'my_subjects' => TeachingAssignment::where('class_room_id', $classId)->count(),
                'new_modules' => LearningModule::whereHas('teachingAssignment', function($q) use ($classId) {
                    $q->where('class_room_id', $classId);
                })->count(),
            ];

            $recentData = LearningModule::whereHas('teachingAssignment', function($q) use ($classId) {
                $q->where('class_room_id', $classId);
            })->with(['teachingAssignment.teacher.user', 'teachingAssignment.subject'])->latest()->take(5)->get();
        }

        return view('backend.dashboard.index', compact('stats', 'recentData'));
    }
}