<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\PostStatus;
use App\Models\BlogPost;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BlogPostRepository implements BlogPostRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, BlogPost>
     */
    public function paginate(int $perPage, ?string $search, bool $publishedOnly, ?PostStatus $status = null): LengthAwarePaginator
    {
        $perPage = min(max($perPage, 1), 100);

        return BlogPost::query()
            ->with(['user', 'category'])
            ->withCount(['comments', 'likes'])
            ->when($publishedOnly, fn (Builder $query): Builder => $query->published())
            ->when($status, function (Builder $query, PostStatus $status): void {
                $query->where('status', $status->value);
            })
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('excerpt', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate($perPage);
    }

    public function find(int $id): BlogPost
    {
        return BlogPost::query()
            ->with(['user', 'category', 'tags'])
            ->withCount(['comments', 'likes'])
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): BlogPost
    {
        return BlogPost::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BlogPost $post, array $data): BlogPost
    {
        $post->update($data);

        return $post;
    }

    public function delete(BlogPost $post): void
    {
        $post->delete();
    }

    public function incrementViews(BlogPost $post): void
    {
        BlogPost::withoutTimestamps(fn () => $post->increment('views_count'));
    }
}
