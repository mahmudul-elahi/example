<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Public\IndexCommentRequest;
use App\Http\Resources\V1\CommentResource;
use App\Models\PetMemorial;
use App\Services\CommentServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Spatie\ResponseCache\Attributes\Cache;

#[Group('Memorials', weight: 10)]
class CommentController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CommentServiceInterface $comments,
    ) {}

    #[Cache(lifetime: 600, tags: ['comments'])]
    public function index(IndexCommentRequest $request, PetMemorial $memorial): JsonResponse
    {
        $comments = $this->comments->paginate($memorial, $request->perPage());

        return $this->paginated($comments, CommentResource::collection($comments->items()));
    }
}
