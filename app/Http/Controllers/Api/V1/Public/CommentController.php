<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\ResponseCacheTag;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\IndexCommentRequest;
use App\Http\Resources\V1\CommentResource;
use App\Models\BlogPost;
use App\Models\PetMemorial;
use App\Models\Product;
use App\Services\CommentServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Spatie\ResponseCache\Attributes\Cache;

/**
 * Every commentable shares one cached comment thread, so each resource gets a
 * thin action that delegates to the comment service.
 */
class CommentController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CommentServiceInterface $comments,
    ) {}

    #[Group('Public - Memorials', weight: 10)]
    #[Cache(lifetime: 600, tags: [ResponseCacheTag::Comments->value])]
    public function indexMemorial(IndexCommentRequest $request, PetMemorial $memorial): JsonResponse
    {
        $comments = $this->comments->cursorPaginate($memorial, $request->perPage(), $request->cursor());

        return $this->cursorPaginated($comments, CommentResource::collection($comments->items()));
    }

    #[Group('Public - Blog', weight: 20)]
    #[Cache(lifetime: 600, tags: [ResponseCacheTag::Comments->value])]
    public function indexBlogPost(IndexCommentRequest $request, BlogPost $blogPost): JsonResponse
    {
        $comments = $this->comments->cursorPaginate($blogPost, $request->perPage(), $request->cursor());

        return $this->cursorPaginated($comments, CommentResource::collection($comments->items()));
    }

    #[Group('Public - Shop', weight: 30)]
    #[Cache(lifetime: 600, tags: [ResponseCacheTag::Comments->value])]
    public function indexProduct(IndexCommentRequest $request, Product $product): JsonResponse
    {
        $comments = $this->comments->cursorPaginate($product, $request->perPage(), $request->cursor());

        return $this->cursorPaginated($comments, CommentResource::collection($comments->items()));
    }
}
