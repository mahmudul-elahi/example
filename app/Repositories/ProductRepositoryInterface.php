<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

#[Bind(ProductRepository::class)]
#[Scoped]
interface ProductRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(int $perPage, ?string $search, bool $publishedOnly): LengthAwarePaginator;

    public function find(int $id): Product;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product;

    public function delete(Product $product): void;
}
