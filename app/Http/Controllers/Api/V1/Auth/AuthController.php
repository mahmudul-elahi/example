<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Services\AuthServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

#[Group('Auth', weight: 1)]
class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuthServiceInterface $auth,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->auth->register($request->validated());

        return $this->created([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Registered successfully');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->auth->login($request->validated());

        return $this->success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Logged in successfully');
    }

    public function logout(#[CurrentUser] User $user): JsonResponse
    {
        $this->auth->logout($user);

        return $this->success(null, 'Logged out successfully');
    }

    public function me(#[CurrentUser] User $user): JsonResponse
    {
        return $this->success(new UserResource($this->auth->me($user)));
    }
}
