<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PetMemorial;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

#[Bind(PetMemorialRepository::class)]
#[Scoped]
interface PetMemorialRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, PetMemorial>
     */
    public function paginate(int $perPage, ?string $search, bool $publicOnly): LengthAwarePaginator;

    public function find(int $id): PetMemorial;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PetMemorial;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PetMemorial $memorial, array $data): PetMemorial;

    public function delete(PetMemorial $memorial): void;

    public function incrementViews(PetMemorial $memorial): void;
}
