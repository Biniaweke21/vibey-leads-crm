<?php

namespace App\Mail;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MeetingCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Meeting $meeting, public string $recipientName, public string $portalUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Meeting cancelled: {$this->meeting->title}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.meeting-cancelled', with: ['portalUrl' => $this->portalUrl]);
    }
}
