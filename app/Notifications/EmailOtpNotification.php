<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailOtpNotification extends Notification
{
    public function __construct(private readonly string $code) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your SLSU BARGO verification code')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Use this 6-digit code to verify your email for SLSU BARGO:')
            ->line($this->code)
            ->line('This code expires in 10 minutes. Do not share it with anyone.')
            ->line('If you did not create this account, you can ignore this email.');
    }
}
