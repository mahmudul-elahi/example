<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserSessionResource;
use App\Models\User;
use App\Services\UserSessionServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

#[Group('User - Profile', weight: 45)]
class UserSessionController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected UserSessionServiceInterface $sessions,
    ) {}

    public function index(#[CurrentUser] User $currentUser): JsonResponse
    {
        $sessions = $this->sessions->listSessions($currentUser);

        return $this->success(UserSessionResource::collection($sessions), 'Active sessions retrieved successfully');
    }

    public function destroy(#[CurrentUser] User $currentUser, int $session): JsonResponse
    {
        $this->sessions->revokeSession($currentUser, $session);

        return $this->success(null, 'Session revoked successfully');
    }

    public function destroyOthers(#[CurrentUser] User $currentUser): JsonResponse
    {
        $this->sessions->revokeOtherSessions($currentUser);

        return $this->success(null, 'Other sessions revoked successfully');
    }
}
