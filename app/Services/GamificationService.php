<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Badge;
use App\Models\StudentBadge;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Notification;
use Carbon\Carbon;

class GamificationService
{
    /**
     * Evaluate and award badges for a student submission.
     */
    public static function evaluateSubmission(AssignmentSubmission $submission)
    {
        $student = Student::findOrFail($submission->student_id);
        $assignment = Assignment::findOrFail($submission->assignment_id);

        $submittedAt = Carbon::parse($submission->submitted_at);
        $assignmentCreatedAt = Carbon::parse($assignment->created_at);
        $dueDate = Carbon::parse($assignment->due_date);

        // 1. Check "Pengumpul Kilat" (Quick Submitter) - submitted within 1 hour of creation
        if ($submittedAt->diffInMinutes($assignmentCreatedAt) <= 60) {
            self::awardBadge($student, 'Pengumpul Kilat');
        }

        // 2. Check "Lebih Awal" (Early Bird) - submitted at least 24 hours before deadline
        if ($dueDate->diffInHours($submittedAt, false) <= -24) {
            self::awardBadge($student, 'Lebih Awal');
        }

        // 3. Check "Siswa Rajin" (Diligent Learner) - submitted 3 assignments on or before deadline
        $onTimeCount = AssignmentSubmission::where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->whereHas('assignment', function ($query) {
                $query->whereColumn('assignment_submissions.submitted_at', '<=', 'assignments.due_date');
            })
            ->count();

        if ($onTimeCount >= 3) {
            self::awardBadge($student, 'Siswa Rajin');
        }
    }

    /**
     * Evaluate and award "Nilai Sempurna" badge for score of 100.
     */
    public static function evaluateScore(AssignmentSubmission $submission)
    {
        if ($submission->score == 100) {
            $student = Student::findOrFail($submission->student_id);
            self::awardBadge($student, 'Nilai Sempurna');
        }
    }

    /**
     * Award a badge to a student if they don't already have it.
     */
    private static function awardBadge(Student $student, string $badgeName)
    {
        $badge = Badge::where('name', $badgeName)->first();
        if (!$badge) {
            return;
        }

        // Check if student already has this badge
        $exists = StudentBadge::where('student_id', $student->id)
            ->where('badge_id', $badge->id)
            ->exists();

        if (!$exists) {
            StudentBadge::create([
                'student_id' => $student->id,
                'badge_id' => $badge->id
            ]);

            // Notify student
            Notification::create([
                'user_id' => $student->user_id,
                'title' => 'Lencana Baru: ' . $badge->name . '! 🎉',
                'message' => 'Selamat! Kamu berhasil meraih lencana digital "' . $badge->name . '". ' . $badge->description,
                'type' => 'badge_earned',
                'url' => route('portal.leaderboard')
            ]);
        }
    }
}
