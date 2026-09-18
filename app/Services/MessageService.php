<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FriendRequestStatus;
use App\Models\Conversation;
use App\Models\FriendRequest;
use App\Models\Message;
use App\Models\User;
use App\Repositories\BlockedUserRepositoryInterface;
use App\Repositories\ConversationRepositoryInterface;
use App\Repositories\FriendRequestRepositoryInterface;
use App\Repositories\MessageRepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class MessageService implements MessageServiceInterface
{
    public function __construct(
        protected MessageRepositoryInterface $messages,
        protected ConversationRepositoryInterface $conversations,
        protected FriendRequestRepositoryInterface $friendRequests,
        protected BlockedUserRepositoryInterface $blockedUsers,
    ) {}

    /**
     * @param  array{type: string, body?: string|null, audio_duration?: int|null}  $data
     */
    public function send(User $currentUser, Conversation $conversation, array $data, ?UploadedFile $file = null): Message
    {
        $conversation = $this->ensureParticipant($currentUser, $conversation);

        $otherUser = $conversation->otherUser($currentUser);

        // Guard 1: Must be friends
        $friendship = $this->friendRequests->findBetween($currentUser->id, $otherUser->id);

        if (! $friendship instanceof FriendRequest
            || $friendship->trashed()
            || $friendship->status !== FriendRequestStatus::Accepted) {
            throw ValidationException::withMessages([
                'message' => 'You can only message users who are on your friends list.',
            ]);
        }

        // Guard 2: Neither user can be blocked
        if ($this->blockedUsers->isBlockedEitherWay($currentUser->id, $otherUser->id)) {
            throw ValidationException::withMessages([
                'message' => 'You cannot message this user.',
            ]);
        }

        if ($file instanceof UploadedFile) {
            $data['attachment_path'] = $file->store('messages');
            $data['attachment_mime'] = $file->getMimeType();
            $data['attachment_size'] = (int) $file->getSize();
        }

        try {
            return $this->messages->create($conversation, $currentUser, $data);
        } catch (Throwable $e) {
            if (isset($data['attachment_path'])) {
                Storage::delete($data['attachment_path']);
            }

            throw $e;
        }
    }

    /**
     * @return CursorPaginator<int, Message>
     */
    public function listMessages(User $currentUser, Conversation $conversation, int $perPage, ?string $cursor): CursorPaginator
    {
        $conversation = $this->ensureParticipant($currentUser, $conversation);

        return $this->messages->paginateForUser($conversation, $currentUser, $perPage, $cursor);
    }

    public function deleteMessage(User $currentUser, Conversation $conversation, Message $message, bool $forEveryone): void
    {
        $conversation = $this->ensureParticipant($currentUser, $conversation);

        if ($message->conversation_id !== $conversation->id) {
            throw (new ModelNotFoundException)->setModel(Message::class, [$message->id]);
        }

        if ($forEveryone) {
            if ($message->sender_id !== $currentUser->id) {
                throw ValidationException::withMessages([
                    'message' => 'You can only delete your own messages for everyone.',
                ]);
            }

            $attachmentPath = $message->attachment_path;

            $this->messages->deleteForEveryone($message);

            if (is_string($attachmentPath) && $attachmentPath !== '') {
                Storage::delete($attachmentPath);
            }

            return;
        }

        $this->messages->deleteForUser($message, $currentUser);
    }

    private function ensureParticipant(User $currentUser, Conversation $conversation): Conversation
    {
        $resolved = $this->conversations->findForUser($currentUser, $conversation->id);

        if (! $resolved instanceof Conversation) {
            throw (new ModelNotFoundException)->setModel(Conversation::class, [$conversation->id]);
        }

        return $resolved;
    }
}
