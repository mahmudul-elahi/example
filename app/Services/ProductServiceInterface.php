<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

#[Bind(ProductService::class)]
#[Scoped]
interface ProductServiceInterface
{
    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(int $perPage, ?string $search, bool $publishedOnly, ?ProductStatus $status = null): LengthAwarePaginator;

    public function find(int $id): Product;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $author, array $data): Product;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product;

    public function delete(Product $product): void;
}
