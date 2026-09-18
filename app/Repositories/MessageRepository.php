<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageDeletion;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MessageRepository implements MessageRepositoryInterface
{
    /**
     * @param  array{type: string, body?: string|null, attachment_path?: string|null, attachment_mime?: string|null, attachment_size?: int|null, audio_duration?: int|null}  $data
     */
    public function create(Conversation $conversation, User $sender, array $data): Message
    {
        return DB::transaction(function () use ($conversation, $sender, $data): Message {
            /** @var Message $message */
            $message = $conversation->messages()->create([
                'sender_id' => $sender->id,
                'type' => $data['type'],
                'body' => $data['body'] ?? null,
                'attachment_path' => $data['attachment_path'] ?? null,
                'attachment_mime' => $data['attachment_mime'] ?? null,
                'attachment_size' => $data['attachment_size'] ?? null,
                'audio_duration' => $data['audio_duration'] ?? null,
            ]);

            // Touch conversation last_message_at
            $conversation->update(['last_message_at' => $message->created_at]);

            // Also mark sender's last_read_at as now
            $conversation->participants()
                ->where('user_id', $sender->id)
                ->update(['last_read_at' => $message->created_at]);

            return $message->load(['sender']);
        });
    }

    public function findInConversation(Conversation $conversation, int $messageId): ?Message
    {
        return $conversation->messages()
            ->where('id', $messageId)
            ->with(['sender'])
            ->first();
    }

    /**
     * @return CursorPaginator<int, Message>
     */
    public function paginateForUser(Conversation $conversation, User $user, int $perPage, ?string $cursor): CursorPaginator
    {
        $participant = $conversation->participants
            ->firstWhere('user_id', $user->id);

        $query = Message::query()
            ->where('conversation_id', $conversation->id)
            ->whereDoesntHave('deletions', function (Builder $q) use ($user): void {
                $q->where('user_id', $user->id);
            });

        if ($participant instanceof ConversationParticipant && $participant->cleared_at !== null) {
            $query->where('created_at', '>', $participant->cleared_at);
        }

        return $query->with(['sender'])
            ->latest('id')
            ->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
    }

    public function deleteForUser(Message $message, User $user): void
    {
        MessageDeletion::query()->firstOrCreate([
            'message_id' => $message->id,
            'user_id' => $user->id,
        ]);
    }

    public function deleteForEveryone(Message $message): void
    {
        $message->update([
            'deleted_for_everyone_at' => now(),
            'body' => null,
            'attachment_path' => null,
            'attachment_mime' => null,
            'attachment_size' => null,
            'audio_duration' => null,
        ]);
    }
}
