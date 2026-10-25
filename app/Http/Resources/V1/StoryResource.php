<?php

namespace App\Http\Resources\V1;

use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Story */
class StoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'caption' => $this->caption,
            'media_type' => $this->media_type->value,
            'media_url' => $this->mediaUrl(),
            'expires_at' => $this->expires_at->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'author' => new AuthorResource($this->whenLoaded('user')),
        ];
    }

    private function mediaUrl(): string
    {
        return url('/api/v1/stories/'.$this->id.'/media');
    }
}
