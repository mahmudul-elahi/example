<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\PasskeyLoginVerifyRequest;
use App\Http\Requests\Api\V1\Auth\PasskeyRegisterVerifyRequest;
use App\Http\Requests\Api\V1\Auth\PasskeyUpdateRequest;
use App\Http\Resources\V1\PasskeyResource;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Services\PasskeyServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Passkey;

#[Group('Auth', weight: 1)]
class PasskeyController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PasskeyServiceInterface $passkeys,
    ) {}

    public function loginOptions(Request $request): JsonResponse
    {
        $options = $this->passkeys->generateLoginOptions($request->input('email'));

        return $this->success($options, 'Passkey login options generated');
    }

    public function loginVerify(PasskeyLoginVerifyRequest $request): JsonResponse
    {
        $result = $this->passkeys->verifyAndLogin($request->validated());

        return $this->success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Logged in successfully');
    }

    public function registerOptions(#[CurrentUser] User $user): JsonResponse
    {
        $options = $this->passkeys->generateRegisterOptions($user);

        return $this->success($options, 'Passkey creation options generated');
    }

    public function registerVerify(PasskeyRegisterVerifyRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $passkey = $this->passkeys->verifyAndRegister($user, $request->validated());

        return $this->created(new PasskeyResource($passkey), 'Passkey registered successfully');
    }

    public function index(#[CurrentUser] User $user): JsonResponse
    {
        $passkeys = $this->passkeys->listUserPasskeys($user);

        return $this->success(PasskeyResource::collection($passkeys));
    }

    public function update(PasskeyUpdateRequest $request, #[CurrentUser] User $user, Passkey $passkey): JsonResponse
    {
        $updated = $this->passkeys->renamePasskey($user, $passkey, $request->validated('name'));

        return $this->success(new PasskeyResource($updated), 'Passkey updated successfully');
    }

    public function destroy(#[CurrentUser] User $user, Passkey $passkey): JsonResponse
    {
        $this->passkeys->deletePasskey($user, $passkey);

        return $this->success(null, 'Passkey deleted successfully');
    }
}
