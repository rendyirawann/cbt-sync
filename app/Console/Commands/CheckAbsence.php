<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Services\ParentNotificationService;
use Carbon\Carbon;

class CheckAbsence extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:check-absence';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for students who have not attended and notify parents';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $settings = AttendanceSetting::first();
        if (!$settings) return;

        $now = Carbon::now();
        $today = Carbon::today();
        $departureStart = Carbon::createFromFormat('H:i:s', $settings->departure_start);

        // We only check if it's past the departure start time (end of school arrival window)
        if ($now->lt($departureStart)) {
            $this->info('Not yet time to check for daily absence.');
            return;
        }

        $students = Student::with('user')->get();
        $notificationService = new ParentNotificationService();

        foreach ($students as $student) {
            $exists = Attendance::where('user_id', $student->user_id)
                ->where('type', 'datang')
                ->whereDate('created_at', $today)
                ->exists();

            if (!$exists) {
                // Mark as Alpa if not already marked
                $alreadyAlpa = Attendance::where('user_id', $student->user_id)
                    ->where('type', 'datang')
                    ->where('status', 'alpa')
                    ->whereDate('created_at', $today)
                    ->exists();

                if (!$alreadyAlpa) {
                    $attendance = Attendance::create([
                        'user_id' => $student->user_id,
                        'type' => 'datang',
                        'status' => 'alpa',
                        'attended_at' => $now,
                        'notes' => 'Tidak melakukan absensi datang'
                    ]);

                    $this->info("Student {$student->user->name} marked as Alpa.");
                    $notificationService->notifyAttendance($attendance);
                }
            }
        }

        $this->info('Absence check completed.');
    }
}
