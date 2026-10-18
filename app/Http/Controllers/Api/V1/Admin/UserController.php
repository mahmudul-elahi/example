<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexUserRequest;
use App\Http\Requests\Api\V1\Admin\StoreUserRequest;
use App\Http\Requests\Api\V1\Admin\UpdateUserRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Services\UserServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

#[Group('Admin - User', weight: 100)]
class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected UserServiceInterface $userService,
    ) {}

    public function index(IndexUserRequest $request): JsonResponse
    {
        $users = $this->userService->getUsers(
            perPage: $request->perPage(),
            search: $request->search(),
            role: $request->role(),
        );

        return $this->paginated($users, UserResource::collection($users->items()));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return $this->created(new UserResource($user));
    }

    public function show(User $user): JsonResponse
    {
        return $this->success(new UserResource($this->userService->find($user->id)));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $updatedUser = $this->userService->update($user, $request->validated());

        return $this->success(new UserResource($updatedUser));
    }

    public function destroy(User $user, #[CurrentUser] User $actor): JsonResponse
    {
        $this->userService->delete($user, $actor);

        return $this->success(null, 'User deleted successfully');
    }
}
