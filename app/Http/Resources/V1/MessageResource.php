<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Message
 */
class MessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isDeletedForEveryone = $this->isDeletedForEveryone();

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender' => new AuthorResource($this->sender),
            'is_mine' => $request->user()?->id === $this->sender_id,
            'type' => $this->type->value,
            'body' => $isDeletedForEveryone ? 'This message was deleted' : $this->body,
            'attachment_url' => $isDeletedForEveryone ? null : $this->attachment_url,
            'attachment_mime' => $isDeletedForEveryone ? null : $this->attachment_mime,
            'attachment_size' => $isDeletedForEveryone ? null : $this->attachment_size,
            'audio_duration' => $isDeletedForEveryone ? null : $this->audio_duration,
            'deleted_for_everyone' => $isDeletedForEveryone,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
