<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\ResponseCacheTag;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\IndexProductRequest;
use App\Http\Requests\Api\V1\Public\ShowProductRequest;
use App\Http\Resources\V1\ProductResource;
use App\Models\Product;
use App\Services\ProductServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Spatie\ResponseCache\Attributes\Cache;

#[Group('Shop', weight: 30)]
class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ProductServiceInterface $products,
    ) {}

    #[Cache(lifetime: 600, tags: [ResponseCacheTag::Shop->value])]
    public function index(IndexProductRequest $request): JsonResponse
    {
        $products = $this->products->paginate($request->perPage(), $request->search(), true);

        return $this->paginated($products, ProductResource::collection($products->items()));
    }

    #[Cache(lifetime: 600, tags: [ResponseCacheTag::Shop->value])]
    public function show(ShowProductRequest $request, Product $product): JsonResponse
    {
        return $this->success(new ProductResource($this->products->find($product->id)));
    }
}
