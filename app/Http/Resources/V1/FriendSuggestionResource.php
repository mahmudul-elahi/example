<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A user the authenticated user could send a friend request to, ranked by how
 * many friends the two already share.
 *
 * @mixin User
 *
 * @property-read int $mutual_friends_count
 * @property-read string|null $avatar_url
 */
class FriendSuggestionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'avatar' => $this->avatar_url,
            'mutual_friends_count' => (int) $this->mutual_friends_count,
        ];
    }
}
