---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Return responses via the ApiResponse trait
Controllers use `App\Traits\ApiResponse` and respond only through its helpers: `success()`, `created()`, `noContent()`, `error()`, `notFound()`, `unauthorized()`, `forbidden()`, `validationError()`. Keep the envelope `{ success, message, data }` (with `errors` added when present). Never call `response()->json()` directly in a controller.

## Example

`app/Http/Controllers/PostController.php`

```php
namespace App\Http\Controllers;

use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostServiceInterface;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PostServiceInterface $posts,
    ) {}

    public function index(): JsonResponse
    {
        return $this->success(PostResource::collection($this->posts->all()));
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        return $this->created(new PostResource($this->posts->create($request->validated())));
    }

    public function show(Post $post): JsonResponse
    {
        return $this->success(new PostResource($this->posts->find($post->id)));
    }

    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        return $this->success(new PostResource($this->posts->update($post, $request->validated())));
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->posts->delete($post);

        return $this->noContent();
    }
}
```

**Response shapes:**

```json
{ "success": true, "message": "Success", "data": {} }
{ "success": true, "message": "Resource created successfully", "data": {} }
{ "success": false, "message": "Validation failed", "errors": {} }
```

## Keep layers thin and single-purpose
Controller: validate (Form Request) -> call service -> wrap in Resource -> respond via ApiResponse. Service: business logic and DB::transaction(); no HTTP. Repository: persistence/queries only, no business rules. Nothing crosses these boundaries.

| Layer | Does | Never does |
| --- | --- | --- |
| Controller | validate, delegate to service, wrap in Resource, respond | Eloquent queries, business rules |
| Service | business logic, `DB::transaction()` | HTTP responses, Eloquent query building |
| Repository | Eloquent queries, eager loading | business rules, HTTP |
| Form Request | validation + authorization | business logic |
| Resource | shaping output | queries, business logic |
