---
paths:
  - 'app/Http/Resources/**'
---

# Resources

## Transform output with API Resources
Never return models or raw arrays from controllers. Wrap every payload in an API Resource from app/Http/Resources: a single resource for one model, `::collection` for many.

## Example

`app/Http/Resources/PostResource.php`

```php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
```

**Usage in a controller:**

```php
// One model
return $this->success(new PostResource($post));

// Many models
return $this->success(PostResource::collection($this->posts->all()));
```

**Conditional fields and relationships:**

```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'title' => $this->title,
        'author' => new UserResource($this->whenLoaded('user')),
        'secret' => $this->when($request->user()?->isAdmin(), $this->secret),
    ];
}
```

## Keep pagination meta inside the envelope
When a service returns a paginator, build the envelope yourself: success data = Resource::collection($paginator), with pagination under a meta key (current_page, per_page, total, last_page) and links. Never let the Resource's built-in pagination wrapper nest inside data.

## Example

```php
public function index(): JsonResponse
{
    $posts = $this->posts->paginate(15);

    return $this->success([
        'items' => PostResource::collection($posts->items()),
        'meta' => [
            'current_page' => $posts->currentPage(),
            'per_page' => $posts->perPage(),
            'total' => $posts->total(),
            'last_page' => $posts->lastPage(),
        ],
    ]);
}
```

Envelope: `{ "success": true, "message": "Success", "data": { "items": [ ... ], "meta": { ... } } }`.

The service method would return `LengthAwarePaginator` (e.g. `Post::query()->paginate($perPage)` inside the repository).
