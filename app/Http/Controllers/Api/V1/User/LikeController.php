<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\DeleteLikeRequest;
use App\Http\Requests\Api\V1\User\LikeRequest;
use App\Models\BlogPost;
use App\Models\PetMemorial;
use App\Models\Product;
use App\Models\User;
use App\Services\LikeServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Parameter;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * Every likeable shares one like service: each resource gets a thin store
 * action, while removal addresses the likeable themselves so one route serves
 * all types.
 */
class LikeController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected LikeServiceInterface $likes,
    ) {}

    #[Group('User - Memorials', weight: 40)]
    public function storeMemorial(LikeRequest $request, PetMemorial $memorial, #[CurrentUser] User $user): JsonResponse
    {
        return $this->success(
            ['likes_count' => $this->likes->like($user, $memorial)],
            'Memorial liked'
        );
    }

    #[Group('User - Blog', weight: 50)]
    public function storeBlogPost(LikeRequest $request, BlogPost $blogPost, #[CurrentUser] User $user): JsonResponse
    {
        return $this->success(
            ['likes_count' => $this->likes->like($user, $blogPost)],
            'Post liked'
        );
    }

    #[Group('User - Shop', weight: 60)]
    public function storeProduct(LikeRequest $request, Product $product, #[CurrentUser] User $user): JsonResponse
    {
        return $this->success(
            ['likes_count' => $this->likes->like($user, $product)],
            'Product liked'
        );
    }

    #[Group('User - Likes', weight: 80)]
    #[Parameter('path', 'type', description: 'The content type: memorials, blog-posts or products.')]
    #[Parameter('path', 'id', type: 'integer')]
    public function destroy(DeleteLikeRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        return $this->success(
            ['likes_count' => $this->likes->unlike($user, $request->likeable())],
            'Like removed'
        );
    }
}
