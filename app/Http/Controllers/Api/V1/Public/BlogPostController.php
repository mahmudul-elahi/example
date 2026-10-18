<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\ResponseCacheTag;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\IndexBlogPostRequest;
use App\Http\Requests\Api\V1\Public\ShowBlogPostRequest;
use App\Http\Resources\V1\BlogPostResource;
use App\Models\BlogPost;
use App\Services\BlogPostServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Spatie\ResponseCache\Attributes\Cache;
use Spatie\ResponseCache\Attributes\NoCache;

#[Group('Blog', weight: 20)]
class BlogPostController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected BlogPostServiceInterface $posts,
    ) {}

    #[Cache(lifetime: 600, tags: [ResponseCacheTag::Blog->value])]
    public function index(IndexBlogPostRequest $request): JsonResponse
    {
        $posts = $this->posts->paginate($request->perPage(), $request->search(), true);

        return $this->paginated($posts, BlogPostResource::collection($posts->items()));
    }

    #[NoCache]
    public function show(ShowBlogPostRequest $request, BlogPost $blogPost): JsonResponse
    {
        $this->posts->recordView($blogPost);

        return $this->success(new BlogPostResource($this->posts->find($blogPost->id)));
    }
}
