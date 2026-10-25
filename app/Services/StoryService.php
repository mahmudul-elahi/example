<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StoryMediaType;
use App\Models\Story;
use App\Models\User;
use App\Repositories\StoryRepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class StoryService implements StoryServiceInterface
{
    public function __construct(
        protected StoryRepositoryInterface $stories,
    ) {}

    public function create(User $user, UploadedFile $media, ?string $caption): Story
    {
        $mediaPath = $media->store('stories/'.$user->id);

        if (! is_string($mediaPath)) {
            throw new RuntimeException('Unable to store the story media.');
        }

        try {
            return $this->stories->create([
                'user_id' => $user->id,
                'media_path' => $mediaPath,
                'media_type' => StoryMediaType::fromMimeType((string) $media->getMimeType())->value,
                'caption' => $caption,
                'expires_at' => Date::now()->addDay(),
            ]);
        } catch (Throwable $exception) {
            Storage::delete($mediaPath);

            throw $exception;
        }
    }

    /** @return CursorPaginator<int, Story> */
    public function feed(User $user, int $perPage, ?string $cursor): CursorPaginator
    {
        return $this->stories->paginateActiveForFriends($user, $perPage, $cursor);
    }

    /** @return CursorPaginator<int, Story> */
    public function mine(User $user, int $perPage, ?string $cursor): CursorPaginator
    {
        return $this->stories->paginateActiveForUser($user, $perPage, $cursor);
    }

    public function delete(Story $story): void
    {
        $this->stories->delete($story);
    }
}
