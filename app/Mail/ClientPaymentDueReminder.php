<?php

namespace App\Mail;

use App\Models\InventoryRequest;
use App\Models\RequestInstallment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientPaymentDueReminder extends Mailable
{
    use SerializesModels;

    public function __construct(
        public InventoryRequest $inventoryRequest,
        public RequestInstallment $installment,
        public int $daysRemaining,
    ) {}

    public function envelope(): Envelope
    {
        $amount = '₱'.number_format($this->installment->balanceDue(), 2);
        $subject = $this->daysRemaining === 0
            ? "Payment due today — {$amount}"
            : "Payment due in {$this->daysRemaining} day".($this->daysRemaining === 1 ? '' : 's')." — {$amount}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.client-payment-due',
        );
    }
}
