<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read array{memorials_count: int, likes_count: int, comments_count: int} $resource
 */
class UserStatsResource extends JsonResource
{
    /**
     * @return array{memorials_count: int, likes_count: int, comments_count: int}
     */
    public function toArray(Request $request): array
    {
        return [
            'memorials_count' => (int) ($this->resource['memorials_count'] ?? 0),
            'likes_count' => (int) ($this->resource['likes_count'] ?? 0),
            'comments_count' => (int) ($this->resource['comments_count'] ?? 0),
        ];
    }
}
