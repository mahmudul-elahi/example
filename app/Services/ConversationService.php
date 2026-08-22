<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FriendRequestStatus;
use App\Models\Conversation;
use App\Models\FriendRequest;
use App\Models\User;
use App\Repositories\BlockedUserRepositoryInterface;
use App\Repositories\ConversationRepositoryInterface;
use App\Repositories\FriendRequestRepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class ConversationService implements ConversationServiceInterface
{
    public function __construct(
        protected ConversationRepositoryInterface $conversations,
        protected FriendRequestRepositoryInterface $friendRequests,
        protected BlockedUserRepositoryInterface $blockedUsers,
    ) {}

    public function startOrGetDirect(User $currentUser, User $recipient): Conversation
    {
        if ($currentUser->is($recipient)) {
            throw ValidationException::withMessages([
                'recipient_id' => 'You cannot start a conversation with yourself.',
            ]);
        }

        $friendship = $this->friendRequests->findBetween($currentUser->id, $recipient->id);

        if (! $friendship instanceof FriendRequest
            || $friendship->trashed()
            || $friendship->status !== FriendRequestStatus::Accepted) {
            throw ValidationException::withMessages([
                'recipient_id' => 'You can only message users who are on your friends list.',
            ]);
        }

        if ($this->blockedUsers->isBlockedEitherWay($currentUser->id, $recipient->id)) {
            throw ValidationException::withMessages([
                'recipient_id' => 'You cannot message this user.',
            ]);
        }

        $conversation = $this->conversations->findOrCreateDirect($currentUser, $recipient);

        $this->hydrateConversation($conversation, $currentUser);

        return $conversation;
    }

    /**
     * @return CursorPaginator<int, Conversation>
     */
    public function listForUser(User $currentUser, int $perPage, ?string $cursor): CursorPaginator
    {
        $paginator = $this->conversations->paginateForUser($currentUser, $perPage, $cursor);
        $items = $paginator->items();

        $this->conversations->loadLatestVisibleMessages($items, $currentUser);

        $counts = $this->conversations->unreadCountsFor(
            array_map(fn (Conversation $conversation): int => $conversation->id, $items),
            $currentUser,
        );

        foreach ($items as $conversation) {
            $conversation->setAttribute('unread_count', $counts[$conversation->id] ?? 0);
        }

        return $paginator;
    }

    public function getForUser(User $currentUser, Conversation $conversation): Conversation
    {
        $conversation = $this->ensureParticipant($currentUser, $conversation);

        $this->hydrateConversation($conversation, $currentUser);

        return $conversation;
    }

    public function deleteForUser(User $currentUser, Conversation $conversation): void
    {
        $conversation = $this->ensureParticipant($currentUser, $conversation);

        $this->conversations->clearForUser($conversation, $currentUser);
    }

    public function markAsRead(User $currentUser, Conversation $conversation): void
    {
        $conversation = $this->ensureParticipant($currentUser, $conversation);

        $this->conversations->markAsRead($conversation, $currentUser);
    }

    private function ensureParticipant(User $currentUser, Conversation $conversation): Conversation
    {
        $resolved = $this->conversations->findForUser($currentUser, $conversation->id);

        if (! $resolved instanceof Conversation) {
            throw (new ModelNotFoundException)->setModel(Conversation::class, [$conversation->id]);
        }

        return $resolved;
    }

    private function hydrateConversation(Conversation $conversation, User $currentUser): void
    {
        $this->conversations->loadLatestVisibleMessages([$conversation], $currentUser);

        $counts = $this->conversations->unreadCountsFor([$conversation->id], $currentUser);

        $conversation->setAttribute('unread_count', $counts[$conversation->id] ?? 0);
    }
}
