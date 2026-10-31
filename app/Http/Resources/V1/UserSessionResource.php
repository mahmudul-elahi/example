<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * @mixin PersonalAccessToken
 *
 * @property string|null $ip_address
 */
class UserSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentTokenId = $request->user()?->currentAccessToken()?->id;

        return [
            'id' => $this->id,
            'device_name' => $this->name,
            'ip_address' => $this->ip_address,
            'is_current' => $currentTokenId !== null && (int) $this->id === (int) $currentTokenId,
            'last_active_at' => ($this->last_used_at ?? $this->created_at)?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
