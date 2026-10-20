<?php

declare(strict_types=1);

namespace App\Notifications\Base;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base class for one-time-password emails.
 *
 * OTP notifications share the same code/expiry payload and the custom HTML +
 * plain-text Blade pair required for notification emails. Subclasses only name
 * their subject and view.
 */
abstract class OtpNotification extends Notification implements ShouldQueue
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
        $data = [
            'code' => $this->code,
            'expiresInMinutes' => $this->expiresInMinutes,
        ];

        return (new MailMessage)
            ->subject($this->subject())
            ->view($this->view(), $data)
            ->text($this->textView(), $data);
    }

    /**
     * The email subject line.
     */
    abstract protected function subject(): string;

    /**
     * The HTML Blade view for the email.
     */
    abstract protected function view(): string;

    /**
     * The plain-text counterpart, following the repo's `*-text` view convention.
     */
    protected function textView(): string
    {
        return $this->view().'-text';
    }
}
