<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $code,
        public readonly int $expiresInMinutes,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your password reset code')
            ->greeting('Hello!')
            ->line('We received a request to reset your password. Use the code below to continue:')
            ->line($this->code)
            ->line('This code expires in '.$this->expiresInMinutes.' minutes.')
            ->line('If you did not request a password reset, you can safely ignore this email.');
    }
}
