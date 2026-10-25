<?php

namespace App\Repositories;

use App\Models\Story;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Carbon;

#[Bind(StoryRepository::class)]
#[Scoped]
interface StoryRepositoryInterface
{
    /** @param array{user_id: int, media_path: string, media_type: string, caption: string|null, expires_at: Carbon} $data */
    public function create(array $data): Story;

    /** @return CursorPaginator<int, Story> */
    public function paginateActiveForFriends(User $user, int $perPage, ?string $cursor): CursorPaginator;

    /** @return CursorPaginator<int, Story> */
    public function paginateActiveForUser(User $user, int $perPage, ?string $cursor): CursorPaginator;

    public function delete(Story $story): void;
}
