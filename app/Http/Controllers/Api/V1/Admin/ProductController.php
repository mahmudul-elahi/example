<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexProductRequest;
use App\Http\Requests\Api\V1\Admin\StoreProductRequest;
use App\Http\Requests\Api\V1\Admin\UpdateProductRequest;
use App\Http\Resources\V1\ProductResource;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Admin - Shop', weight: 140)]
class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ProductServiceInterface $products,
    ) {}

    public function index(IndexProductRequest $request): JsonResponse
    {
        $products = $this->products->paginate($request->perPage(), $request->search(), false);

        return $this->paginated($products, ProductResource::collection($products->items()));
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $product = $this->products->create($user, $request->validated());

        return $this->created(new ProductResource($this->products->find($product->id)));
    }

    public function show(Product $product): JsonResponse
    {
        return $this->success(new ProductResource($this->products->find($product->id)));
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->products->update($product, $request->validated());

        return $this->success(new ProductResource($this->products->find($product->id)));
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->products->delete($product);

        return $this->success(null, 'Product deleted successfully');
    }
}
