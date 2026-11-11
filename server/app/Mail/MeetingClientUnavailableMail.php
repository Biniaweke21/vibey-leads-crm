<?php

namespace App\Mail;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MeetingClientUnavailableMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Meeting $meeting, public string $recipientName, public string $portalUrl) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Client unavailable: {$this->meeting->title}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.meeting-client-unavailable', with: ['portalUrl' => $this->portalUrl]);
    }
}
