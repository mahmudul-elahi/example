<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\ResponseCacheTag;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\IndexPetMemorialRequest;
use App\Http\Requests\Api\V1\Public\ShowPetMemorialRequest;
use App\Http\Resources\V1\PetMemorialResource;
use App\Http\Resources\V1\PetMemorialSummaryResource;
use App\Models\PetMemorial;
use App\Services\PetMemorialServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Spatie\ResponseCache\Attributes\Cache;
use Spatie\ResponseCache\Attributes\NoCache;

#[Group('Public - Memorials', weight: 10)]
class PetMemorialController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PetMemorialServiceInterface $memorials,
    ) {}

    #[Cache(lifetime: 600, tags: [ResponseCacheTag::Memorials->value])]
    public function index(IndexPetMemorialRequest $request): JsonResponse
    {
        $memorials = $this->memorials->cursorPaginate($request->perPage(), $request->search(), true, $request->cursor());

        return $this->cursorPaginated($memorials, PetMemorialSummaryResource::collection($memorials->items()));
    }

    #[NoCache]
    public function show(ShowPetMemorialRequest $request, PetMemorial $memorial): JsonResponse
    {
        $this->memorials->recordView($memorial);

        return $this->success(new PetMemorialResource($this->memorials->find($memorial->id)));
    }
}
