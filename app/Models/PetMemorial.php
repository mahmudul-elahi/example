<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PetType;
use App\Enums\PostStatus;
use App\Enums\VisibilityStatus;
use App\Observers\PetMemorialObserver;
use Cviebrock\EloquentSluggable\Sluggable;
use Database\Factories\PetMemorialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property-read int $user_id
 * @property-read string $slug
 * @property-read string $body
 * @property-read string $pet_name
 * @property-read PetType|null $pet_type
 * @property-read Carbon|null $pet_born_at
 * @property-read Carbon|null $pet_passed_at
 * @property-read PostStatus $status
 * @property-read VisibilityStatus $visibility
 * @property-read int $views_count
 */
#[Fillable([
    'user_id',
    'slug',
    'body',
    'pet_name',
    'pet_type',
    'pet_born_at',
    'pet_passed_at',
    'status',
    'visibility',
    'views_count',
])]
#[RouteKey('slug')]
#[ObservedBy([PetMemorialObserver::class])]
class PetMemorial extends Model
{
    /** @use HasFactory<PetMemorialFactory> */
    use HasFactory, Sluggable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pet_type' => PetType::class,
            'pet_born_at' => 'date',
            'pet_passed_at' => 'date',
            'status' => PostStatus::class,
            'visibility' => VisibilityStatus::class,
            'views_count' => 'integer',
        ];
    }

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array<string, array<string, string>>
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'pet_name',
            ],
        ];
    }

    /**
     * @return HasMany<PetMemorialImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(PetMemorialImage::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphMany<Comment, $this>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * @return MorphMany<Like, $this>
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Scope to memorials that are visible to the public: published and public.
     *
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function publiclyVisible(Builder $query): void
    {
        $query->where('status', PostStatus::Published->value)
            ->where('visibility', VisibilityStatus::Public->value);
    }
}
