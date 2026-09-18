<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Http\UploadedFile;

#[Bind(MessageService::class)]
#[Scoped]
interface MessageServiceInterface
{
    /**
     * @param  array{type: string, body?: string|null, audio_duration?: int|null}  $data
     */
    public function send(User $currentUser, Conversation $conversation, array $data, ?UploadedFile $file = null): Message;

    /**
     * @return CursorPaginator<int, Message>
     */
    public function listMessages(User $currentUser, Conversation $conversation, int $perPage, ?string $cursor): CursorPaginator;

    public function deleteMessage(User $currentUser, Conversation $conversation, Message $message, bool $forEveryone): void;
}
