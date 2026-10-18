<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\DeleteCommentRequest;
use App\Http\Requests\Api\V1\User\StoreCommentRequest;
use App\Http\Resources\V1\CommentResource;
use App\Models\BlogPost;
use App\Models\PetMemorial;
use App\Models\Product;
use App\Models\User;
use App\Services\CommentServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Parameter;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/**
 * Every commentable shares one comment service, so each resource gets a thin
 * store action while deletion addresses the content type and comment id.
 */
class CommentController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CommentServiceInterface $comments,
    ) {}

    #[Group('Memorials', weight: 10)]
    public function storeMemorial(StoreCommentRequest $request, PetMemorial $memorial, #[CurrentUser] User $user): JsonResponse
    {
        $comment = $this->comments->create($user, $memorial, $request->validated());

        return $this->created(new CommentResource($this->comments->find($comment->id)));
    }

    #[Group('Blog', weight: 20)]
    public function storeBlogPost(StoreCommentRequest $request, BlogPost $blogPost, #[CurrentUser] User $user): JsonResponse
    {
        $comment = $this->comments->create($user, $blogPost, $request->validated());

        return $this->created(new CommentResource($this->comments->find($comment->id)));
    }

    #[Group('Shop', weight: 30)]
    public function storeProduct(StoreCommentRequest $request, Product $product, #[CurrentUser] User $user): JsonResponse
    {
        $comment = $this->comments->create($user, $product, $request->validated());

        return $this->created(new CommentResource($this->comments->find($comment->id)));
    }

    #[Group('Memorials', weight: 10)]
    #[Parameter('path', 'type', description: 'The content type: memorials, blog-posts or products.')]
    #[Parameter('path', 'comment', description: 'The comment ID', type: 'integer')]
    public function destroy(DeleteCommentRequest $request): JsonResponse
    {
        $this->comments->delete($request->comment());

        return $this->success(null, 'Comment deleted successfully');
    }
}
