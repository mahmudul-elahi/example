<?php

namespace App\Services;

use App\Models\Story;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Http\UploadedFile;

#[Bind(StoryService::class)]
#[Scoped]
interface StoryServiceInterface
{
    public function create(User $user, UploadedFile $media, ?string $caption): Story;

    /** @return CursorPaginator<int, Story> */
    public function feed(User $user, int $perPage, ?string $cursor): CursorPaginator;

    /** @return CursorPaginator<int, Story> */
    public function mine(User $user, int $perPage, ?string $cursor): CursorPaginator;

    public function delete(Story $story): void;
}
