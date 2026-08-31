<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendDeadlineReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-deadline-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mencari tugas yang hampir deadline dalam 24 jam ke depan dan mengirim pengingat otomatis ke siswa dan email orang tua.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai pengiriman pengingat deadline tugas...');

        $now = \Carbon\Carbon::now();
        $tomorrow = \Carbon\Carbon::now()->addHours(24);

        // Ambil semua tugas yang due date-nya dalam 24 jam ke depan
        $assignments = \App\Models\Assignment::whereBetween('due_date', [$now, $tomorrow])
            ->with(['teachingAssignment.classRoom', 'teachingAssignment.subject'])
            ->get();

        if ($assignments->isEmpty()) {
            $this->info('Tidak ada tugas dengan tenggat waktu dalam 24 jam ke depan.');
            return 0;
        }

        $reminderCount = 0;

        foreach ($assignments as $assignment) {
            $classId = $assignment->teachingAssignment->class_room_id;

            // Ambil semua siswa di kelas tersebut
            $studentsInClass = \App\Models\ClassStudent::where('class_room_id', $classId)
                ->whereHas('academicYear', function ($q) {
                    $q->where('is_active', 1);
                })
                ->with('student.user')
                ->get();

            foreach ($studentsInClass as $cs) {
                $student = $cs->student;
                if (!$student || !$student->user) continue;

                // Cek apakah siswa sudah mengirim tugas ini
                $hasSubmitted = \App\Models\AssignmentSubmission::where('assignment_id', $assignment->id)
                    ->where('student_id', $student->id)
                    ->exists();

                if (!$hasSubmitted) {
                    $this->line("Mengirim pengingat untuk: {$student->user->name} -> Tugas: {$assignment->title}");
                    \App\Services\NotificationService::sendDeadlineReminder($assignment, $student);
                    $reminderCount++;
                }
            }
        }

        $this->info("Pengiriman selesai! Total {$reminderCount} pengingat terkirim.");
        return 0;
    }
}
