<?php

namespace App\Mail;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Document $invoice) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Payment reminder — Invoice {$this->invoice->number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-reminder',
            with: ['portalUrl' => rtrim(config('services.frontend.url'), '/').'/client/login'],
        );
    }
}
