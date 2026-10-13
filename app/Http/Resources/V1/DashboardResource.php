<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps an aggregate array rather than a model, so it references itself to
 * satisfy Scramble's model resolution (JR001) while `toArray`'s return type
 * remains the source of the schema.
 *
 * @mixin DashboardResource
 */
class DashboardResource extends JsonResource
{
    /**
     * @return array{users: array{users: int, new_last_7_days: int}}
     */
    public function toArray(Request $request): array
    {
        return [
            'users' => [
                'users' => (int) $this->resource['users']['users'],
                'new_last_7_days' => (int) $this->resource['users']['new_last_7_days'],
            ],
        ];
    }
}
