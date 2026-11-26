<?php

namespace App\Mail;

use App\Models\InventoryRequest;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientPaymentFullyPaid extends Mailable
{
    use SerializesModels;

    public function __construct(public InventoryRequest $inventoryRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment complete — '.$this->inventoryRequest->request_no,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.client-payment-paid',
        );
    }
}
