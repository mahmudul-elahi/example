<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PetMemorial;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

#[Bind(PetMemorialService::class)]
#[Scoped]
interface PetMemorialServiceInterface
{
    /**
     * @return LengthAwarePaginator<int, PetMemorial>
     */
    public function paginate(int $perPage, ?string $search, bool $publicOnly): LengthAwarePaginator;

    public function find(int $id): PetMemorial;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $author, array $data): PetMemorial;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PetMemorial $memorial, array $data): PetMemorial;

    public function delete(PetMemorial $memorial): void;

    public function recordView(PetMemorial $memorial): void;
}
