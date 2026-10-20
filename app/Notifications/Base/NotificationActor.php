<?php

declare(strict_types=1);

namespace App\Notifications\Base;

use App\Models\User;

/**
 * The user who triggered a notification, in the shape clients expect.
 *
 * Notifications carry this as plain data instead of an Eloquent model so the
 * queued payload stays small and immutable.
 */
final readonly class NotificationActor
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $avatarUrl = null,
    ) {}

    public static function fromUser(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            avatarUrl: $user->avatar_url,
        );
    }

    /**
     * @return array{id: int, name: string, avatar_url: string|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar_url' => $this->avatarUrl,
        ];
    }
}
