<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(int $perPage, ?string $search, bool $publishedOnly, ?ProductStatus $status = null): LengthAwarePaginator
    {
        $perPage = min(max($perPage, 1), 100);

        return Product::query()
            ->with('user')
            ->withCount(['comments', 'likes'])
            ->when($publishedOnly, fn (Builder $query): Builder => $query->published())
            ->when($status, function (Builder $query, ProductStatus $status): void {
                $query->where('status', $status->value);
            })
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate($perPage);
    }

    public function find(int $id): Product
    {
        return Product::query()
            ->with('user')
            ->withCount(['comments', 'likes'])
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Product
    {
        return Product::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
