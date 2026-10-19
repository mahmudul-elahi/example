<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Roles;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Assign a single role, replacing any previously assigned role.
     */
    public function setRole(Roles $role): static
    {
        $this->syncRoles([RoleModel::findOrCreate($role->value)]);

        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(Roles::Admin->value);
    }

    /**
     * The user's single role as a string, or null when unassigned.
     */
    protected function role(): Attribute
    {
        return Attribute::make(get: fn () => $this->roles->first()?->name);
    }

    /**
     * The absolute URL of the user's avatar, or null when none is set.
     *
     * @return Attribute<string|null, never>
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->avatar === null ? null : Storage::url($this->avatar),
        );
    }

    /**
     * The pet memorials authored by the user.
     *
     * @return HasMany<PetMemorial, $this>
     */
    public function petMemorials(): HasMany
    {
        return $this->hasMany(PetMemorial::class);
    }

    /**
     * The blog posts authored by the user.
     *
     * @return HasMany<BlogPost, $this>
     */
    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    /**
     * The shop products created by the user.
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * The comments written by the user.
     *
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * The likes given by the user.
     *
     * @return HasMany<Like, $this>
     */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    /**
     * The friend requests the user has sent.
     *
     * @return HasMany<FriendRequest, $this>
     */
    public function sentFriendRequests(): HasMany
    {
        return $this->hasMany(FriendRequest::class, 'sender_id');
    }

    /**
     * The friend requests the user has received.
     *
     * @return HasMany<FriendRequest, $this>
     */
    public function receivedFriendRequests(): HasMany
    {
        return $this->hasMany(FriendRequest::class, 'receiver_id');
    }
}
