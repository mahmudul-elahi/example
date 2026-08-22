<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\CursorPaginator;

#[Bind(ConversationRepository::class)]
#[Scoped]
interface ConversationRepositoryInterface
{
    /**
     * Create or retrieve a 1-on-1 direct conversation.
     */
    public function findOrCreateDirect(User $userA, User $userB): Conversation;

    /**
     * Find conversation by ID ensuring the user is a participant.
     */
    public function findForUser(User $user, int $conversationId): ?Conversation;

    /**
     * Paginate active conversations for a user.
     *
     * @return CursorPaginator<int, Conversation>
     */
    public function paginateForUser(User $user, int $perPage, ?string $cursor): CursorPaginator;

    /**
     * Clear/delete conversation for the given user only.
     */
    public function clearForUser(Conversation $conversation, User $user): void;

    /**
     * Mark all messages in conversation as read for the user.
     */
    public function markAsRead(Conversation $conversation, User $user): void;

    /**
     * Attach the latest message visible to the user to each conversation.
     *
     * @param  iterable<int, Conversation>  $conversations
     */
    public function loadLatestVisibleMessages(iterable $conversations, User $user): void;

    /**
     * Count unread messages for each conversation in a single query.
     *
     * @param  array<int, int>  $conversationIds
     * @return array<int, int>
     */
    public function unreadCountsFor(array $conversationIds, User $user): array;
}
