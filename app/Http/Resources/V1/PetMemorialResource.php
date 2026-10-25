<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\PetMemorial;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PetMemorial
 *
 * @property string|null $cover_image_url
 */
class PetMemorialResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'body' => $this->body,
            'pet_name' => $this->pet_name,
            'pet_type' => $this->pet_type?->value,
            'pet_born_at' => $this->pet_born_at?->toDateString(),
            'pet_passed_at' => $this->pet_passed_at?->toDateString(),
            'cover_image' => $this->cover_image_url,
            'status' => $this->status->value,
            'visibility' => $this->visibility->value,
            'views_count' => $this->views_count,
            'comments_count' => $this->whenCounted('comments'),
            'likes_count' => $this->whenCounted('likes'),
            'author' => new AuthorResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
