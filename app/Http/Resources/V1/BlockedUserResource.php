<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\BlockedUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BlockedUser
 */
class BlockedUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'blocked_user' => new AuthorResource($this->blocked),
            'blocked_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
