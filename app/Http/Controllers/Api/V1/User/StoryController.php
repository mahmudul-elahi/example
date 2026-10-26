<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\DeleteStoryRequest;
use App\Http\Requests\Api\V1\User\IndexStoryRequest;
use App\Http\Requests\Api\V1\User\StoreStoryRequest;
use App\Http\Requests\Api\V1\User\ViewStoryMediaRequest;
use App\Http\Resources\V1\StoryResource;
use App\Models\Story;
use App\Models\User;
use App\Services\StoryServiceInterface;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Group('User - Stories', weight: 96)]
class StoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected StoryServiceInterface $stories,
    ) {}

    public function index(IndexStoryRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $stories = $this->stories->feed($user, $request->perPage(), $request->cursor());

        return $this->cursorPaginated($stories, StoryResource::collection($stories->items()), 'Friend stories');
    }

    public function mine(IndexStoryRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $stories = $this->stories->mine($user, $request->perPage(), $request->cursor());

        return $this->cursorPaginated($stories, StoryResource::collection($stories->items()), 'My stories');
    }

    public function store(StoreStoryRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $story = $this->stories->create($user, $request->media(), $request->caption());

        return $this->created(new StoryResource($story), 'Story created successfully');
    }

    public function media(ViewStoryMediaRequest $request, Story $story): StreamedResponse
    {
        abort_unless(Storage::exists($story->media_path), 404);

        return Storage::response($story->media_path);
    }

    public function destroy(DeleteStoryRequest $request, Story $story): JsonResponse
    {
        $this->stories->delete($story);

        return $this->success(null, 'Story deleted successfully');
    }
}
