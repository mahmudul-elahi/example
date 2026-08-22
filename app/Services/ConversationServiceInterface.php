<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\CursorPaginator;

#[Bind(ConversationService::class)]
#[Scoped]
interface ConversationServiceInterface
{
    public function startOrGetDirect(User $currentUser, User $recipient): Conversation;

    /**
     * @return CursorPaginator<int, Conversation>
     */
    public function listForUser(User $currentUser, int $perPage, ?string $cursor): CursorPaginator;

    public function getForUser(User $currentUser, Conversation $conversation): Conversation;

    public function deleteForUser(User $currentUser, Conversation $conversation): void;

    public function markAsRead(User $currentUser, Conversation $conversation): void;
}
