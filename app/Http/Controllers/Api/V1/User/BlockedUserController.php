<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\IndexBlockedUserRequest;
use App\Http\Resources\V1\BlockedUserResource;
use App\Models\User;
use App\Services\BlockedUserServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

#[Group('User - Blocks', weight: 87)]
class BlockedUserController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected BlockedUserServiceInterface $blockedUsers,
    ) {}

    public function index(IndexBlockedUserRequest $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $paginator = $this->blockedUsers->listBlocked(
            $currentUser,
            $request->perPage(),
            $request->cursor(),
        );

        return $this->cursorPaginated(
            $paginator,
            BlockedUserResource::collection($paginator->items()),
        );
    }

    public function block(User $user, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->blockedUsers->block($currentUser, $user);

        return $this->success(null, 'User blocked successfully');
    }

    public function unblock(User $user, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->blockedUsers->unblock($currentUser, $user);

        return $this->success(null, 'User unblocked successfully');
    }
}
