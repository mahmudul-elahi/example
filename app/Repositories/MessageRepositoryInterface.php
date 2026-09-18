<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\CursorPaginator;

#[Bind(MessageRepository::class)]
#[Scoped]
interface MessageRepositoryInterface
{
    /**
     * @param  array{type: string, body?: string|null, attachment_path?: string|null, attachment_mime?: string|null, attachment_size?: int|null, audio_duration?: int|null}  $data
     */
    public function create(Conversation $conversation, User $sender, array $data): Message;

    public function findInConversation(Conversation $conversation, int $messageId): ?Message;

    /**
     * @return CursorPaginator<int, Message>
     */
    public function paginateForUser(Conversation $conversation, User $user, int $perPage, ?string $cursor): CursorPaginator;

    public function deleteForUser(Message $message, User $user): void;

    public function deleteForEveryone(Message $message): void;
}
