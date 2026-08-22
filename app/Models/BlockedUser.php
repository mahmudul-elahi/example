<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BlockedUserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $blocker_id
 * @property int $blocked_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $blocker
 * @property-read User $blocked
 */
#[Fillable(['blocker_id', 'blocked_id'])]
class BlockedUser extends Model
{
    /** @use HasFactory<BlockedUserFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocker_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function blocked(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_id');
    }
}
