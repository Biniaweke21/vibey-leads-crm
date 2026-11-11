<?php

namespace App\Mail;

use App\Models\Document;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Document $document) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->document->type->label()}: {$this->document->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-sent',
            with: ['portalUrl' => rtrim(config('services.frontend.url'), '/').'/client/login'],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $pdf = Pdf::loadView('documents.pdf', [
            'document' => $this->document,
            'company' => Setting::get('company', []),
        ])->output();

        $filename = ($this->document->number ?? $this->document->type->value).'-'.$this->document->id.'.pdf';

        return [
            Attachment::fromData(fn () => $pdf, $filename)->withMime('application/pdf'),
        ];
    }
}
