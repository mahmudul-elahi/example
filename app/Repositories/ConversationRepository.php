<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ConversationRepository implements ConversationRepositoryInterface
{
    public function findOrCreateDirect(User $userA, User $userB): Conversation
    {
        $userOneId = min($userA->id, $userB->id);
        $userTwoId = max($userA->id, $userB->id);

        return DB::transaction(function () use ($userOneId, $userTwoId, $userA, $userB): Conversation {
            /** @var Conversation $conversation */
            $conversation = Conversation::query()->createOrFirst([
                'user_one_id' => $userOneId,
                'user_two_id' => $userTwoId,
            ]);

            $conversation->participants()->firstOrCreate(['user_id' => $userA->id]);
            $conversation->participants()->firstOrCreate(['user_id' => $userB->id]);

            return $conversation->load(['userOne', 'userTwo', 'participants']);
        });
    }

    public function findForUser(User $user, int $conversationId): ?Conversation
    {
        return Conversation::query()
            ->where('id', $conversationId)
            ->whereHas('participants', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->with(['userOne', 'userTwo', 'participants'])
            ->first();
    }

    /**
     * @return CursorPaginator<int, Conversation>
     */
    public function paginateForUser(User $user, int $perPage, ?string $cursor): CursorPaginator
    {
        return Conversation::query()
            ->whereHas('participants', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->where(function (Builder $q): void {
                        $q->whereNull('cleared_at')
                            ->orWhereColumn('conversations.last_message_at', '>', 'conversation_participants.cleared_at');
                    });
            })
            ->whereNotNull('last_message_at')
            ->with(['userOne', 'userTwo', 'participants'])
            ->latest('last_message_at')
            ->latest('id')
            ->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
    }

    public function clearForUser(Conversation $conversation, User $user): void
    {
        $conversation->participants()
            ->where('user_id', $user->id)
            ->update(['cleared_at' => now()]);
    }

    public function markAsRead(Conversation $conversation, User $user): void
    {
        $conversation->participants()
            ->where('user_id', $user->id)
            ->update(['last_read_at' => now()]);
    }

    /**
     * Attach the latest message visible to the user to each conversation.
     *
     * @param  iterable<int, Conversation>  $conversations
     */
    public function loadLatestVisibleMessages(iterable $conversations, User $user): void
    {
        $conversations = collect($conversations);

        if ($conversations->isEmpty()) {
            return;
        }

        $conversationIds = $conversations->pluck('id')->all();

        $latestMessageIds = Message::query()
            ->whereIn('conversation_id', $conversationIds)
            ->whereDoesntHave('deletions', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->groupBy('conversation_id')
            ->selectRaw('conversation_id, MAX(id) as id')
            ->pluck('id', 'conversation_id');

        $messages = Message::query()
            ->whereIn('id', $latestMessageIds->values()->all())
            ->with(['sender'])
            ->get()
            ->keyBy('conversation_id');

        foreach ($conversations as $conversation) {
            $conversation->loadMissing('participants');

            $participant = $conversation->participants->firstWhere('user_id', $user->id);
            $message = $messages->get($conversation->id);

            if ($message instanceof Message
                && $participant instanceof ConversationParticipant
                && $participant->cleared_at !== null
                && $message->created_at !== null
                && $message->created_at->lessThanOrEqualTo($participant->cleared_at)) {
                $message = null;
            }

            $conversation->setRelation('latestMessage', $message);
        }
    }

    /**
     * Count unread messages for each conversation in a single query.
     *
     * @param  array<int, int>  $conversationIds
     * @return array<int, int>
     */
    public function unreadCountsFor(array $conversationIds, User $user): array
    {
        if ($conversationIds === []) {
            return [];
        }

        /** @var array<int|string, int|string> $counts */
        $counts = Message::query()
            ->join('conversation_participants', 'conversation_participants.conversation_id', '=', 'messages.conversation_id')
            ->where('conversation_participants.user_id', $user->id)
            ->whereIn('messages.conversation_id', $conversationIds)
            ->where('messages.sender_id', '!=', $user->id)
            ->whereNull('messages.deleted_for_everyone_at')
            ->whereDoesntHave('deletions', function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id);
            })
            ->where(function (Builder $query): void {
                $query->whereNull('conversation_participants.last_read_at')
                    ->orWhereColumn('messages.created_at', '>', 'conversation_participants.last_read_at');
            })
            ->where(function (Builder $query): void {
                $query->whereNull('conversation_participants.cleared_at')
                    ->orWhereColumn('messages.created_at', '>', 'conversation_participants.cleared_at');
            })
            ->groupBy('messages.conversation_id')
            ->selectRaw('messages.conversation_id, COUNT(*) as unread_count')
            ->pluck('unread_count', 'messages.conversation_id')
            ->all();

        return array_map(fn (int|string $count): int => (int) $count, $counts);
    }
}
