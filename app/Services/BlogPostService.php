<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PostStatus;
use App\Models\BlogPost;
use App\Models\User;
use App\Repositories\BlogPostRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class BlogPostService implements BlogPostServiceInterface
{
    public function __construct(
        protected BlogPostRepositoryInterface $repository,
    ) {}

    /**
     * @return LengthAwarePaginator<int, BlogPost>
     */
    public function paginate(int $perPage, ?string $search, bool $publishedOnly): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $search, $publishedOnly);
    }

    public function find(int $id): BlogPost
    {
        return $this->repository->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $author, array $data): BlogPost
    {
        $data['user_id'] = $author->id;
        $data['cover_image'] = $this->storeCoverImage($data['cover_image'] ?? null);
        $data = $this->stampPublication($data);

        return $this->repository->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BlogPost $post, array $data): BlogPost
    {
        $obsoleteCover = array_key_exists('cover_image', $data) ? $post->cover_image : null;

        if (array_key_exists('cover_image', $data)) {
            $data['cover_image'] = $this->storeCoverImage($data['cover_image']);
        }

        $data = $this->stampPublication($data);

        $post = $this->repository->update($post, $data);

        if ($obsoleteCover !== null) {
            Storage::delete($obsoleteCover);
        }

        return $post;
    }

    public function delete(BlogPost $post): void
    {
        $this->repository->delete($post);
    }

    public function recordView(BlogPost $post): void
    {
        $this->repository->incrementViews($post);
    }

    /**
     * Set the publication timestamp the first time a post becomes published.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function stampPublication(array $data): array
    {
        $status = $data['status'] ?? null;
        $becomingPublished = $status instanceof PostStatus
            ? $status === PostStatus::Published
            : $status === PostStatus::Published->value;

        if ($becomingPublished && ! isset($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    /**
     * Store an uploaded cover image on the default disk, or clear the value.
     */
    private function storeCoverImage(mixed $cover): ?string
    {
        if ($cover instanceof UploadedFile) {
            return $cover->store('blog');
        }

        return is_string($cover) ? $cover : null;
    }
}
