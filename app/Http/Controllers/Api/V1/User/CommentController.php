<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\DeleteCommentRequest;
use App\Http\Requests\Api\V1\User\StoreCommentRequest;
use App\Http\Resources\V1\CommentResource;
use App\Models\Comment;
use App\Models\PetMemorial;
use App\Models\User;
use App\Services\CommentServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Memorials', weight: 10)]
class CommentController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CommentServiceInterface $comments,
    ) {}

    public function store(StoreCommentRequest $request, PetMemorial $memorial): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $comment = $this->comments->create($user, $memorial, $request->validated());

        return $this->created(new CommentResource($this->comments->find($comment->id)));
    }

    public function destroy(DeleteCommentRequest $request, Comment $comment): JsonResponse
    {
        $this->comments->delete($comment);

        return $this->success(null, 'Comment deleted successfully');
    }
}
