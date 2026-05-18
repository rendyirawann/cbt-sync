<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\LearningModule;
use App\Models\Student;
use App\Models\Notification;
use App\Models\ClassStudent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Dispatch notifications for a newly created assignment.
     */
    public static function sendAssignmentNotification(Assignment $assignment)
    {
        $teachingAssignment = $assignment->teachingAssignment;
        if (!$teachingAssignment) return;

        $classId = $teachingAssignment->class_room_id;
        $subjectName = $teachingAssignment->subject->name ?? 'Mata Pelajaran';

        // Get all students in the class
        $classStudents = ClassStudent::where('class_room_id', $classId)
            ->whereHas('academicYear', function ($q) {
                $q->where('is_active', 1);
            })
            ->with('student.user')
            ->get();

        foreach ($classStudents as $cs) {
            $student = $cs->student;
            if (!$student || !$student->user) continue;

            // 1. Create In-App Notification
            try {
                Notification::create([
                    'user_id' => $student->user_id,
                    'title' => 'Tugas Baru: ' . $assignment->title . ' 📝',
                    'message' => 'Ada penugasan baru untuk mata pelajaran ' . $subjectName . '. Batas waktu pengumpulan: ' . \Carbon\Carbon::parse($assignment->due_date)->format('d M Y H:i') . ' WIB.',
                    'type' => 'assignment',
                    'url' => route('student.assignments.index')
                ]);
            } catch (\Exception $e) {
                Log::error('Gagal membuat notifikasi in-app tugas: ' . $e->getMessage());
            }

            // 2. Send Parent Email
            if ($student->parent_email) {
                try {
                    $emailData = [
                        'parentName' => $student->parent_name ?: 'Orang Tua/Wali',
                        'studentName' => $student->user->name,
                        'subjectName' => $subjectName,
                        'assignmentTitle' => $assignment->title,
                        'dueDate' => $assignment->due_date,
                        'description' => $assignment->description,
                        'isReminder' => false,
                        'loginUrl' => route('login')
                    ];

                    Mail::send('emails.parent_assignment_notification', $emailData, function ($message) use ($student) {
                        $message->to($student->parent_email)
                            ->subject('Pemberitahuan Tugas Baru: ' . $student->user->name);
                    });
                } catch (\Exception $e) {
                    Log::error('Gagal mengirim email orang tua untuk tugas baru: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Dispatch notifications for a newly created/published learning module.
     */
    public static function sendAnnouncementNotification(LearningModule $module)
    {
        if (!$module->is_published) return;

        $teachingAssignment = $module->teachingAssignment;
        if (!$teachingAssignment) return;

        $classId = $teachingAssignment->class_room_id;
        $subjectName = $teachingAssignment->subject->name ?? 'Mata Pelajaran';

        // Get all students in the class
        $classStudents = ClassStudent::where('class_room_id', $classId)
            ->whereHas('academicYear', function ($q) {
                $q->where('is_active', 1);
            })
            ->with('student.user')
            ->get();

        foreach ($classStudents as $cs) {
            $student = $cs->student;
            if (!$student || !$student->user) continue;

            // 1. Create In-App Notification
            try {
                Notification::create([
                    'user_id' => $student->user_id,
                    'title' => 'Materi/Pengumuman Baru: ' . $module->title . ' 📢',
                    'message' => 'Guru telah mengunggah materi pembelajaran baru untuk mata pelajaran ' . $subjectName . '.',
                    'type' => 'announcement',
                    'url' => route('student.learning-modules.index')
                ]);
            } catch (\Exception $e) {
                Log::error('Gagal membuat notifikasi in-app materi: ' . $e->getMessage());
            }

            // 2. Send Parent Email
            if ($student->parent_email) {
                try {
                    $emailData = [
                        'parentName' => $student->parent_name ?: 'Orang Tua/Wali',
                        'studentName' => $student->user->name,
                        'subjectName' => $subjectName,
                        'moduleTitle' => $module->title,
                        'zoomLink' => $module->zoom_link,
                        'description' => $module->description,
                        'loginUrl' => route('login')
                    ];

                    Mail::send('emails.parent_announcement_notification', $emailData, function ($message) use ($student) {
                        $message->to($student->parent_email)
                            ->subject('Materi & Pengumuman Baru: ' . $student->user->name);
                    });
                } catch (\Exception $e) {
                    Log::error('Gagal mengirim email orang tua untuk materi baru: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Send approaching deadline reminders for assignments due in next 24 hours.
     */
    public static function sendDeadlineReminder(Assignment $assignment, Student $student)
    {
        if (!$student->user) return;

        $teachingAssignment = $assignment->teachingAssignment;
        $subjectName = $teachingAssignment->subject->name ?? 'Mata Pelajaran';

        // 1. Create In-App Notification
        try {
            Notification::create([
                'user_id' => $student->user_id,
                'title' => '🚨 PENGINGAT DEADLINE: ' . $assignment->title,
                'message' => 'Segera kumpulkan tugas mata pelajaran ' . $subjectName . '. Tenggat waktu: ' . \Carbon\Carbon::parse($assignment->due_date)->format('d M Y H:i') . ' WIB.',
                'type' => 'assignment_deadline',
                'url' => route('student.assignments.index')
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal membuat notifikasi in-app pengingat deadline: ' . $e->getMessage());
        }

        // 2. Send Parent Email
        if ($student->parent_email) {
            try {
                $emailData = [
                    'parentName' => $student->parent_name ?: 'Orang Tua/Wali',
                    'studentName' => $student->user->name,
                    'subjectName' => $subjectName,
                    'assignmentTitle' => $assignment->title,
                    'dueDate' => $assignment->due_date,
                    'description' => $assignment->description,
                    'isReminder' => true,
                    'loginUrl' => route('login')
                ];

                Mail::send('emails.parent_assignment_notification', $emailData, function ($message) use ($student) {
                    $message->to($student->parent_email)
                        ->subject('🚨 PENGINGAT BATAS WAKTU TUGAS: ' . $student->user->name);
                });
            } catch (\Exception $e) {
                Log::error('Gagal mengirim email orang tua untuk pengingat deadline: ' . $e->getMessage());
            }
        }
    }
}
