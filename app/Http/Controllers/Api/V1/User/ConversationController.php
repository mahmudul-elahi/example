<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\IndexConversationRequest;
use App\Http\Requests\Api\V1\User\StoreConversationRequest;
use App\Http\Resources\V1\ConversationResource;
use App\Models\Conversation;
use App\Models\User;
use App\Services\ConversationServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

#[Group('User - Conversations', weight: 85)]
class ConversationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ConversationServiceInterface $conversations,
    ) {}

    public function index(IndexConversationRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $paginator = $this->conversations->listForUser(
            $user,
            $request->perPage(),
            $request->cursor(),
        );

        return $this->cursorPaginated(
            $paginator,
            ConversationResource::collection($paginator->items()),
        );
    }

    public function store(StoreConversationRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $conversation = $this->conversations->startOrGetDirect($user, $request->recipient());

        return $this->created(
            new ConversationResource($conversation),
            'Conversation started successfully',
        );
    }

    public function show(Conversation $conversation, #[CurrentUser] User $user): JsonResponse
    {
        $conversation = $this->conversations->getForUser($user, $conversation);

        return $this->success(new ConversationResource($conversation));
    }

    public function destroy(Conversation $conversation, #[CurrentUser] User $user): JsonResponse
    {
        $this->conversations->deleteForUser($user, $conversation);

        return $this->success(null, 'Conversation deleted successfully');
    }

    public function read(Conversation $conversation, #[CurrentUser] User $user): JsonResponse
    {
        $this->conversations->markAsRead($user, $conversation);

        return $this->success(null, 'Conversation marked as read');
    }
}
