<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The lifecycle of a friend request.
 *
 * A request starts as Pending and ends as either Accepted (the two users are
 * friends) or Rejected (the receiver declined). Cancelling a request removes
 * the row entirely instead of moving it to a fourth state.
 */
enum FriendRequestStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
