---
paths:
  - 'app/Repositories/**'
---

# Repositories

## Data access lives in Repositories, bound by interface
Put all Eloquent/database access in app/Repositories. Every repository has an interface (suffixed RepositoryInterface) and a concrete implementation (suffixed Repository). Services type-hint the interface only, never the concrete class.

## Bind interfaces with the #[Bind] attribute
Annotate the interface with Laravel's `#[Bind(Implementation::class)]` attribute (`Illuminate\Container\Attributes\Bind`) so the container resolves it automatically — no service provider registration needed. Add `#[Singleton]` / `#[Scoped]` (`Illuminate\Container\Attributes\*`) when it should resolve once. Use provider `$this->app->bind()` only when a closure or extra setup is required. Multiple `#[Bind]` attributes may target different environments.

## Example

**Interface** — `app/Repositories/PostRepositoryInterface.php`

```php
namespace App\Repositories;

use App\Models\Post;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\Collection;

#[Bind(PostRepository::class)]
#[Singleton]
interface PostRepositoryInterface
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

**Implementation** — `app/Repositories/PostRepository.php`

```php
namespace App\Repositories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;

class PostRepository implements PostRepositoryInterface
{
    /** @return Collection<int, Post> */
    public function all(): Collection
    {
        return Post::query()->latest()->get();
    }

    public function find(int $id): Post
    {
        return Post::query()->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): Post
    {
        return Post::query()->create($data);
    }

    /** @param array<string, mixed> $data */
    public function update(Post $post, array $data): Post
    {
        $post->update($data);

        return $post;
    }

    public function delete(Post $post): void
    {
        $post->delete();
    }
}
```

## Eager load relationships in repositories
Eager load with with()/load() in repository queries so relationships are loaded before resources run. Avoid lazy loading in resources and N+1 queries in index().

## Example

```php
/** @return Collection<int, Post> */
public function all(): Collection
{
    return Post::query()
        ->with(['user', 'comments'])
        ->latest()
        ->get();
}
```
