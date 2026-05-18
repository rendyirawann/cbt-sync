<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Badge;
use App\Models\ClassStudent;
use App\Models\AssignmentSubmission;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        $classId = request('class_room_id');
        $availableClasses = collect();

        if ($user->hasRole('Siswa')) {
            $student = $user->student;
            if (!$student) {
                return redirect()->route('student.dashboard')->with('error', 'Profil siswa tidak ditemukan.');
            }
            
            // Get the active class ID of the logged-in student
            $classId = ClassStudent::where('student_id', $student->id)
                ->whereHas('academicYear', function ($q) {
                    $q->where('is_active', 1);
                })
                ->value('class_room_id');

            if (!$classId) {
                return redirect()->route('student.dashboard')->with('error', 'Anda tidak terdaftar di kelas aktif mana pun.');
            }
        } elseif ($user->hasRole('Guru')) {
            $teacher = $user->teacher;
            if (!$teacher) {
                return redirect()->route('dashboard')->with('error', 'Profil pengajar tidak ditemukan.');
            }

            // Get classrooms taught by this teacher
            $availableClasses = \App\Models\TeachingAssignment::where('teacher_id', $teacher->id)
                ->with('classRoom')
                ->get()
                ->pluck('classRoom')
                ->unique('id')
                ->filter();

            if (!$classId && $availableClasses->isNotEmpty()) {
                $classId = $availableClasses->first()->id;
            }
        } elseif ($user->hasRole('Superadmin')) {
            // Superadmin can see any classroom
            $availableClasses = \App\Models\ClassRoom::all();
            if (!$classId && $availableClasses->isNotEmpty()) {
                $classId = $availableClasses->first()->id;
            }
        }

        if (!$classId) {
            return view('frontend.leaderboard.index', [
                'leaderboard' => [],
                'allBadges' => Badge::all(),
                'myBadgeIds' => [],
                'myRank' => null,
                'availableClasses' => $availableClasses,
                'classId' => null,
                'selectedClass' => null
            ]);
        }

        $selectedClass = \App\Models\ClassRoom::find($classId);

        // Get all students in the selected class to build the class leaderboard
        $classStudents = ClassStudent::where('class_room_id', $classId)
            ->whereHas('academicYear', function ($q) {
                $q->where('is_active', 1);
            })
            ->with(['student.user', 'student.badges'])
            ->get();

        $leaderboard = [];

        foreach ($classStudents as $cs) {
            $s = $cs->student;
            if (!$s || !$s->user) continue;

            // 1. Calculate On-Time Submissions
            $onTimeSubmissions = AssignmentSubmission::where('student_id', $s->id)
                ->whereNotNull('submitted_at')
                ->whereHas('assignment', function ($query) {
                    $query->whereColumn('assignment_submissions.submitted_at', '<=', 'assignments.due_date');
                })
                ->count();

            // 2. Sum of Scores
            $totalScore = AssignmentSubmission::where('student_id', $s->id)->sum('score') ?: 0;

            // 3. Count of Badges
            $badgeCount = $s->badges->count();

            // Calculate Gamification Rank Points
            // Formula: (On-Time Submissions * 50 pts) + (Total score) + (Badges * 100 pts)
            $rankPoints = ($onTimeSubmissions * 50) + $totalScore + ($badgeCount * 100);

            $leaderboard[] = [
                'student_id' => $s->id,
                'name' => $s->user->name,
                'avatar' => $s->user->avatar_url,
                'on_time_count' => $onTimeSubmissions,
                'total_score' => $totalScore,
                'badge_count' => $badgeCount,
                'points' => $rankPoints,
            ];
        }

        // Sort leaderboard by points descending
        usort($leaderboard, function($a, $b) {
            return $b['points'] <=> $a['points'];
        });

        // Add Rank Number and find current student's rank
        $myRank = null;
        $myBadgeIds = [];
        if ($user->hasRole('Siswa') && $user->student) {
            $myBadgeIds = $user->student->badges->pluck('id')->toArray();
        }

        foreach ($leaderboard as $index => &$row) {
            $row['rank'] = $index + 1;
            if ($user->hasRole('Siswa') && $user->student && $row['student_id'] === $user->student->id) {
                $myRank = $row;
            }
        }

        // Fetch all available system badges
        $allBadges = Badge::all();

        return view('frontend.leaderboard.index', compact(
            'leaderboard',
            'allBadges',
            'myBadgeIds',
            'myRank',
            'availableClasses',
            'classId',
            'selectedClass'
        ));
    }
}
