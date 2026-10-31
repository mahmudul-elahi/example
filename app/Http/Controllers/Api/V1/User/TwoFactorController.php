<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\TwoFactorConfirmRequest;
use App\Http\Requests\Api\V1\User\TwoFactorDisableRequest;
use App\Models\User;
use App\Services\TwoFactorServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

#[Group('User - Profile', weight: 45)]
class TwoFactorController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected TwoFactorServiceInterface $twoFactor,
    ) {}

    public function enable(#[CurrentUser] User $currentUser): JsonResponse
    {
        $result = $this->twoFactor->enable($currentUser);

        return $this->success($result, 'Two factor authentication initiated');
    }

    public function confirm(TwoFactorConfirmRequest $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $result = $this->twoFactor->confirm($currentUser, $request->validated('code'));

        return $this->success($result, 'Two factor authentication enabled successfully');
    }

    public function disable(TwoFactorDisableRequest $request, #[CurrentUser] User $currentUser): JsonResponse
    {
        $this->twoFactor->disable($currentUser);

        return $this->success(null, 'Two factor authentication disabled successfully');
    }

    public function recoveryCodes(#[CurrentUser] User $currentUser): JsonResponse
    {
        $result = $this->twoFactor->generateNewRecoveryCodes($currentUser);

        return $this->success($result, 'New recovery codes generated successfully');
    }
}
