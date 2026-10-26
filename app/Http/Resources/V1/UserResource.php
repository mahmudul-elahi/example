<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 *
 * @property string|null $avatar_url
 * @property int|null $pet_memorials_count
 * @property int|null $likes_count
 * @property int|null $comments_count
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $isOwnerOrAdmin = $viewer instanceof User && ($viewer->id === $this->id || $viewer->isAdmin());

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'avatar' => $this->avatar_url,
            'role' => $this->role,
            'badge' => $this->isAdmin() ? 'Administrator' : 'Pet Guardian',
            'email_verified' => $this->hasVerifiedEmail(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'memorials_count' => (int) ($this->pet_memorials_count ?? (
                $isOwnerOrAdmin
                    ? $this->petMemorials()->count()
                    : $this->petMemorials()->publiclyVisible()->count()
            )),
            'likes_count' => (int) ($this->likes_count ?? $this->likes()->count()),
            'comments_count' => (int) ($this->comments_count ?? $this->comments()->count()),
        ];
    }
}
