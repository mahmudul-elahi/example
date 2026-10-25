<?php

namespace App\Models;

use App\Enums\StoryMediaType;
use App\Observers\StoryObserver;
use Database\Factories\StoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property int $user_id
 * @property string $media_path
 * @property StoryMediaType $media_type
 * @property string|null $caption
 * @property Carbon $expires_at
 * @property-read Carbon|null $created_at
 * @property-read User $user
 */
#[Fillable(['user_id', 'media_path', 'media_type', 'caption', 'expires_at'])]
#[ObservedBy([StoryObserver::class])]
class Story extends Model
{
    /** @use HasFactory<StoryFactory> */
    use HasFactory, Prunable;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'media_type' => StoryMediaType::class,
            'expires_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get stories that have passed their 24-hour lifetime.
     *
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('expires_at', '<=', now());
    }
}
