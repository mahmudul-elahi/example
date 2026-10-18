<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexBlogPostRequest;
use App\Http\Requests\Api\V1\Admin\StoreBlogPostRequest;
use App\Http\Requests\Api\V1\Admin\UpdateBlogPostRequest;
use App\Http\Resources\V1\BlogPostResource;
use App\Models\BlogPost;
use App\Models\User;
use App\Services\BlogPostServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

#[Group('Admin - Blog', weight: 120)]
class BlogPostController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected BlogPostServiceInterface $posts,
    ) {}

    public function index(IndexBlogPostRequest $request): JsonResponse
    {
        $posts = $this->posts->paginate($request->perPage(), $request->search(), false);

        return $this->paginated($posts, BlogPostResource::collection($posts->items()));
    }

    public function store(StoreBlogPostRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $post = $this->posts->create($user, $request->validated());

        return $this->created(new BlogPostResource($this->posts->find($post->id)));
    }

    public function show(BlogPost $blogPost): JsonResponse
    {
        return $this->success(new BlogPostResource($this->posts->find($blogPost->id)));
    }

    public function update(UpdateBlogPostRequest $request, BlogPost $blogPost): JsonResponse
    {
        $this->posts->update($blogPost, $request->validated());

        return $this->success(new BlogPostResource($this->posts->find($blogPost->id)));
    }

    public function destroy(BlogPost $blogPost): JsonResponse
    {
        $this->posts->delete($blogPost);

        return $this->success(null, 'Blog post deleted successfully');
    }
}
