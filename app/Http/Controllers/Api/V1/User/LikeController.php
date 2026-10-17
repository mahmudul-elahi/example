<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\LikeMemorialRequest;
use App\Models\PetMemorial;
use App\Models\User;
use App\Services\LikeServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Memorials', weight: 10)]
class LikeController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected LikeServiceInterface $likes,
    ) {}

    public function store(LikeMemorialRequest $request, PetMemorial $memorial): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success(
            ['likes_count' => $this->likes->like($user, $memorial)],
            'Memorial liked'
        );
    }

    public function destroy(LikeMemorialRequest $request, PetMemorial $memorial): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success(
            ['likes_count' => $this->likes->unlike($user, $memorial)],
            'Memorial unliked'
        );
    }
}
