<?php

declare(strict_types=1);

namespace App\Notifications\Base;

use App\Enums\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Base class for every in-app notification.
 *
 * A concrete notification only describes itself: its type, its message, and
 * the type-specific payload. The envelope and database storage are handled
 * here, so new types stay small and the stored shape never drifts.
 */
abstract class InAppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The stable discriminator clients switch on.
     */
    abstract public function type(): NotificationType;

    /**
     * The human readable text shown in the notification list.
     */
    abstract public function message(): string;

    /**
     * The type-specific payload, for example actors or related IDs.
     *
     * @return array<string, mixed>
     */
    abstract public function payload(): array;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Store the enum value instead of the class name so renaming or moving a
     * notification class never invalidates existing rows.
     */
    final public function databaseType(object $notifiable): string
    {
        return $this->type()->value;
    }

    /**
     * @return array{message: string, data: array<string, mixed>}
     */
    final public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->message(),
            'data' => $this->payload(),
        ];
    }
}
