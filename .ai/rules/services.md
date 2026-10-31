---
paths:
  - 'app/Services/**'
---

# Services

## Business logic lives in Services, bound by interface
Put business logic in app/Services, one class per resource/concept. Every service has an interface (suffixed ServiceInterface) and a concrete implementation (suffixed Service). Controllers stay thin — validate, call the service, return a response — and type-hint the interface only, never the concrete class.

## Bind interfaces with the #[Bind] attribute
Annotate the interface with Laravel's `#[Bind(Implementation::class)]` attribute (`Illuminate\Container\Attributes\Bind`) so the container resolves it automatically — no service provider registration needed. Add `#[Singleton]` / `#[Scoped]` (`Illuminate\Container\Attributes\*`) when it should resolve once. Use provider `$this->app->bind()` only when a closure or extra setup is required. Multiple `#[Bind]` attributes may target different environments.

## Example

**Interface** — `app/Services/PostServiceInterface.php`

```php
namespace App\Services;

use App\Models\Post;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\Collection;

#[Bind(PostService::class)]
#[Singleton]
interface PostServiceInterface
{
    /** @return Collection<int, Post> */
    public function all(): Collection;

    public function find(int $id): Post;

    /** @param array<string, mixed> $data */
    public function create(array $data): Post;

    /** @param array<string, mixed> $data */
    public function update(Post $post, array $data): Post;

    public function delete(Post $post): void;
}
```

**Implementation** — `app/Services/PostService.php`

```php
namespace App\Services;

use App\Models\Post;
use App\Repositories\PostRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PostService implements PostServiceInterface
{
    public function __construct(
        protected PostRepositoryInterface $repository,
    ) {}

    /** @return Collection<int, Post> */
    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function find(int $id): Post
    {
        return $this->repository->find($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Post
    {
        return $this->repository->create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(Post $post, array $data): Post
    {
        return $this->repository->update($post, $data);
    }

    public function delete(Post $post): void
    {
        $this->repository->delete($post);
    }
}
```

## Process and optimize images with Laravel's Image facade
Always optimize user-uploaded images using Laravel's built-in `Illuminate\Support\Facades\Image` facade before storing them on disk. Always scale down oversized dimensions (such as 48MP smartphone photos) to prevent memory bloat and convert to WebP:

```php
use Illuminate\Support\Facades\Image;

// Content covers and gallery images (e.g. memorials, blog, products):
$path = Image::fromUpload($file)
    ->scale(width: 2560)
    ->optimize()
    ->store('memorials');

// User avatars:
$path = Image::fromUpload($file)
    ->scale(width: 1024)
    ->optimize()
    ->store('avatars');
```

Never store raw unoptimized images directly.
