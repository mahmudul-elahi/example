<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;
use App\Repositories\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductService implements ProductServiceInterface
{
    public function __construct(
        protected ProductRepositoryInterface $repository,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginate(int $perPage, ?string $search, bool $publishedOnly, ?ProductStatus $status = null): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $search, $publishedOnly, $status);
    }

    public function find(int $id): Product
    {
        return $this->repository->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $author, array $data): Product
    {
        $data['user_id'] = $author->id;
        $data['cover_image'] = $this->storeCoverImage($data['cover_image'] ?? null);

        return $this->repository->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Product $product, array $data): Product
    {
        $obsoleteCover = array_key_exists('cover_image', $data) ? $product->cover_image : null;

        if (array_key_exists('cover_image', $data)) {
            $data['cover_image'] = $this->storeCoverImage($data['cover_image']);
        }

        $product = $this->repository->update($product, $data);

        if ($obsoleteCover !== null) {
            Storage::delete($obsoleteCover);
        }

        return $product;
    }

    public function delete(Product $product): void
    {
        $this->repository->delete($product);
    }

    /**
     * Store an uploaded cover image on the default disk, or clear the value.
     */
    private function storeCoverImage(mixed $cover): ?string
    {
        if ($cover instanceof UploadedFile) {
            return $cover->store('products');
        }

        return is_string($cover) ? $cover : null;
    }
}
