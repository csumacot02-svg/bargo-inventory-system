<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyInventoryDigest extends Mailable
{
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public array $payload) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'BARGO daily digest — '.$this->payload['date'],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.daily-digest',
        );
    }
}
