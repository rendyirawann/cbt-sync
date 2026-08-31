<?php

namespace App\Mail;

use App\Models\Attendance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ParentAttendanceNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $attendance;
    public $studentName;

    /**
     * Create a new message instance.
     */
    public function __construct(Attendance $attendance, $studentName)
    {
        $this->attendance = $attendance;
        $this->studentName = $studentName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $type = ucfirst($this->attendance->type);
        $status = ucfirst($this->attendance->status);
        return new Envelope(
            subject: "Notifikasi Absensi {$status}: {$this->studentName} ({$type})",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.parent_attendance_notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
