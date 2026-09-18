<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\DeleteMessageRequest;
use App\Http\Requests\Api\V1\User\IndexMessageRequest;
use App\Http\Requests\Api\V1\User\SendMessageRequest;
use App\Http\Resources\V1\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\MessageServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

#[Group('User - Messages', weight: 86)]
class MessageController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected MessageServiceInterface $messages,
    ) {}

    public function index(IndexMessageRequest $request, #[CurrentUser] User $user, Conversation $conversation): JsonResponse
    {
        $paginator = $this->messages->listMessages(
            $user,
            $conversation,
            $request->perPage(),
            $request->cursor(),
        );

        return $this->cursorPaginated(
            $paginator,
            MessageResource::collection($paginator->items()),
        );
    }

    public function store(SendMessageRequest $request, #[CurrentUser] User $user, Conversation $conversation): JsonResponse
    {
        $message = $this->messages->send(
            $user,
            $conversation,
            $request->validated(),
            $request->file('file'),
        );

        return $this->created(
            new MessageResource($message),
            'Message sent successfully',
        );
    }

    public function destroy(DeleteMessageRequest $request, #[CurrentUser] User $user, Conversation $conversation, Message $message): JsonResponse
    {
        $this->messages->deleteMessage(
            $user,
            $conversation,
            $message,
            $request->forEveryone(),
        );

        return $this->success(null, 'Message deleted successfully');
    }
}
