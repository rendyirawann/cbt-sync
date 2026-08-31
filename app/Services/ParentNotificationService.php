<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\User;
use App\Mail\ParentAttendanceNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ParentNotificationService
{
    public function notifyAttendance(Attendance $attendance)
    {
        $user = User::with('student')->find($attendance->user_id);
        if (!$user || !$user->student || !$user->student->parent_email) {
            Log::info('ParentNotification: Skipped - No parent email for user ' . ($user->name ?? $attendance->user_id));
            return;
        }

        $parentEmail = $user->student->parent_email;
        $studentName = $user->name;

        try {
            Mail::to($parentEmail)->send(new ParentAttendanceNotification($attendance, $studentName));
            Log::info("ParentNotification: Email sent to {$parentEmail} for student {$studentName} ({$attendance->type}/{$attendance->status})");
        } catch (\Exception $e) {
            Log::error("ParentNotification: Failed to send email to {$parentEmail} - " . $e->getMessage());
        }
    }
}
