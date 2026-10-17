<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\IndexPetMemorialRequest;
use App\Http\Requests\Api\V1\Public\ShowPetMemorialRequest;
use App\Http\Resources\V1\PetMemorialResource;
use App\Models\PetMemorial;
use App\Services\PetMemorialServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Memorials', weight: 10)]
class PetMemorialController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PetMemorialServiceInterface $memorials,
    ) {}

    public function index(IndexPetMemorialRequest $request): JsonResponse
    {
        $memorials = $this->memorials->paginate($request->perPage(), $request->search(), true);

        return $this->paginated($memorials, PetMemorialResource::collection($memorials->items()));
    }

    public function show(ShowPetMemorialRequest $request, PetMemorial $memorial): JsonResponse
    {
        $this->memorials->recordView($memorial);

        return $this->success(new PetMemorialResource($this->memorials->find($memorial->id)));
    }
}
