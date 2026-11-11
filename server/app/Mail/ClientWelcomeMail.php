<?php

namespace App\Mail;

use App\Models\Client;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $password, public Client $client) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Welcome to the {$this->client->name} Client Portal",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client-welcome',
            with: ['portalUrl' => rtrim(config('services.frontend.url'), '/').'/client/login'],
        );
    }
}
