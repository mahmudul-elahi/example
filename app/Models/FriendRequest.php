<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FriendRequestStatus;
use Database\Factories\FriendRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property-read int $sender_id
 * @property-read int $receiver_id
 * @property-read FriendRequestStatus $status
 * @property-read Carbon|null $responded_at
 */
#[Fillable(['sender_id', 'receiver_id', 'status', 'responded_at'])]
class FriendRequest extends Model
{
    /** @use HasFactory<FriendRequestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FriendRequestStatus::class,
            'responded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
