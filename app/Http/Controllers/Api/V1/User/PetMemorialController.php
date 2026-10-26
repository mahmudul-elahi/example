<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\DeletePetMemorialRequest;
use App\Http\Requests\Api\V1\User\IndexPetMemorialRequest;
use App\Http\Requests\Api\V1\User\StorePetMemorialRequest;
use App\Http\Requests\Api\V1\User\UpdatePetMemorialRequest;
use App\Http\Resources\V1\PetMemorialResource;
use App\Models\PetMemorial;
use App\Models\User;
use App\Services\PetMemorialServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

#[Group('User - Memorials', weight: 40)]
class PetMemorialController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PetMemorialServiceInterface $memorials,
    ) {}

    public function index(IndexPetMemorialRequest $request): JsonResponse
    {
        $memorials = $this->memorials->cursorPaginate($request->perPage(), $request->search(), true, $request->cursor());

        return $this->cursorPaginated($memorials, PetMemorialResource::collection($memorials->items()));
    }

    public function mine(IndexPetMemorialRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $memorials = $this->memorials->mine($user, $request->perPage(), $request->search(), $request->cursor());

        return $this->cursorPaginated($memorials, PetMemorialResource::collection($memorials->items()), 'My memorials');
    }

    public function store(StorePetMemorialRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $memorial = $this->memorials->create($user, $request->validated());

        return $this->created(new PetMemorialResource($this->memorials->find($memorial->id)));
    }

    public function update(UpdatePetMemorialRequest $request, PetMemorial $memorial): JsonResponse
    {
        $this->memorials->update($memorial, $request->validated());

        return $this->success(new PetMemorialResource($this->memorials->find($memorial->id)));
    }

    public function destroy(DeletePetMemorialRequest $request, PetMemorial $memorial): JsonResponse
    {
        $this->memorials->delete($memorial);

        return $this->success(null, 'Memorial deleted successfully');
    }
}
