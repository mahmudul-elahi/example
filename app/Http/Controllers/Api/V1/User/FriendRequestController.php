<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\DeleteFriendRequestRequest;
use App\Http\Requests\Api\V1\User\IndexFriendRequestRequest;
use App\Http\Requests\Api\V1\User\IndexFriendSuggestionRequest;
use App\Http\Requests\Api\V1\User\RespondFriendRequestRequest;
use App\Http\Requests\Api\V1\User\StoreFriendRequestRequest;
use App\Http\Resources\V1\AuthorResource;
use App\Http\Resources\V1\FriendRequestResource;
use App\Http\Resources\V1\FriendSuggestionResource;
use App\Models\FriendRequest;
use App\Models\User;
use App\Services\FriendRequestServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * The friend request lifecycle: send, list received, accept, reject, cancel,
 * list friends, list suggestions and unfriend. Accepted requests become
 * friendships.
 */
#[Group('User - Friends', weight: 90)]
class FriendRequestController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected FriendRequestServiceInterface $friendRequests,
    ) {}

    public function index(IndexFriendRequestRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $requests = $this->friendRequests->received($user, $request->perPage(), $request->cursor());

        return $this->cursorPaginated($requests, FriendRequestResource::collection($requests->items()));
    }

    public function count(#[CurrentUser] User $user): JsonResponse
    {
        return $this->success(['pending_count' => $this->friendRequests->pendingCount($user)]);
    }

    public function store(StoreFriendRequestRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $friendRequest = $this->friendRequests->send($user, $request->receiver());

        return $this->created(new FriendRequestResource($friendRequest), 'Friend request sent');
    }

    public function accept(RespondFriendRequestRequest $request, FriendRequest $friendRequest, #[CurrentUser] User $user): JsonResponse
    {
        $friendRequest = $this->friendRequests->accept($user, $friendRequest);

        return $this->success(new FriendRequestResource($friendRequest), 'Friend request accepted');
    }

    public function reject(RespondFriendRequestRequest $request, FriendRequest $friendRequest, #[CurrentUser] User $user): JsonResponse
    {
        $friendRequest = $this->friendRequests->reject($user, $friendRequest);

        return $this->success(new FriendRequestResource($friendRequest), 'Friend request rejected');
    }

    public function destroy(DeleteFriendRequestRequest $request, FriendRequest $friendRequest, #[CurrentUser] User $user): JsonResponse
    {
        $this->friendRequests->cancel($user, $friendRequest);

        return $this->success(null, 'Friend request cancelled');
    }

    public function friends(IndexFriendRequestRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $friends = $this->friendRequests->friends($user, $request->perPage(), $request->cursor());

        return $this->cursorPaginated($friends, AuthorResource::collection($friends->items()), 'Friends');
    }

    public function suggestions(IndexFriendSuggestionRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        return $this->success(
            FriendSuggestionResource::collection($this->friendRequests->suggestions($user)),
            'Friend suggestions'
        );
    }

    public function unfriend(User $friend, #[CurrentUser] User $user): JsonResponse
    {
        $this->friendRequests->unfriend($user, $friend);

        return $this->success(null, 'Friend removed');
    }
}
