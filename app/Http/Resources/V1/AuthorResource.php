<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The public-safe author summary embedded in content and comment payloads.
 *
 * It deliberately omits the account email and role so browsing content never
 * exposes another user's private details, and never triggers a lazy load of
 * the roles relation.
 *
 * @mixin User
 *
 * @property-read string|null $avatar_url
 */
class AuthorResource extends JsonResource
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
        ];
    }
}
