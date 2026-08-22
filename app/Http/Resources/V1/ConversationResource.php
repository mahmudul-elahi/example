<?php

declare(strict_types=1);

namespace App\Http\Resources\V1;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Conversation
 */
class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $currentUser */
        $currentUser = $request->user();
        $recipient = $this->otherUser($currentUser);

        return [
            'id' => $this->id,
            'recipient' => new AuthorResource($recipient),
            'last_message' => $this->latestMessage instanceof Message
                ? new MessageResource($this->latestMessage)
                : null,
            'unread_count' => (int) ($this->resource->getAttribute('unread_count') ?? 0),
            'last_message_at' => $this->last_message_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
