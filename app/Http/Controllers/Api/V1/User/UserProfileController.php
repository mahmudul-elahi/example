<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\IndexPetMemorialRequest;
use App\Http\Requests\Api\V1\User\IndexStoryRequest;
use App\Http\Requests\Api\V1\User\UpdateAvatarRequest;
use App\Http\Resources\V1\PetMemorialResource;
use App\Http\Resources\V1\StoryResource;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Services\BlockedUserServiceInterface;
use App\Services\PetMemorialServiceInterface;
use App\Services\StoryServiceInterface;
use App\Services\UserServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

#[Group('User - Profile', weight: 45)]
class UserProfileController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected UserServiceInterface $userService,
        protected BlockedUserServiceInterface $blockedUsers,
        protected PetMemorialServiceInterface $memorials,
        protected StoryServiceInterface $stories,
    ) {}

    public function show(User $user, #[CurrentUser] User $currentUser): JsonResponse
    {
        abort_if($this->blockedUsers->isBlockedEitherWay($currentUser->id, $user->id), 404, 'User not found');

        $profile = $this->userService->getProfile($user, $currentUser);

        return $this->success(new UserResource($profile));
    }

    public function memorials(IndexPetMemorialRequest $request, User $user, #[CurrentUser] User $currentUser): JsonResponse
    {
        abort_if($this->blockedUsers->isBlockedEitherWay($currentUser->id, $user->id), 404, 'User not found');

        $isOwnerOrAdmin = $currentUser->id === $user->id || $currentUser->isAdmin();
        $memorials = $isOwnerOrAdmin
            ? $this->memorials->mine($user, $request->perPage(), $request->search(), $request->cursor())
            : $this->memorials->forUser($user, $request->perPage(), $request->search(), $request->cursor(), true);

        return $this->cursorPaginated($memorials, PetMemorialResource::collection($memorials->items()), 'User memorials');
    }

    public function stories(IndexStoryRequest $request, User $user, #[CurrentUser] User $currentUser): JsonResponse
    {
        abort_if($this->blockedUsers->isBlockedEitherWay($currentUser->id, $user->id), 404, 'User not found');

        $stories = $this->stories->forUser($user, $request->perPage(), $request->cursor());

        return $this->cursorPaginated($stories, StoryResource::collection($stories->items()), 'User stories');
    }

    public function updateAvatar(UpdateAvatarRequest $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $user = $this->userService->updateAvatar($currentUser, $request->avatar());

        return $this->success(new UserResource($user), 'Profile image updated successfully');
    }
}
