---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Group controllers by audience
Put each controller in the subfolder matching the route file that registers it: `Api\V1\Auth`, `Api\V1\Public`, `Api\V1\User`, `Api\V1\Admin` (see `.ai/rules/http.md`). Guest-readable actions (no auth) live in `Public`; actions behind `auth:sanctum` live in `User`. Split a class that would serve both audiences into a `Public` and a `User` controller, and register each method in the matching route file. Controllers keep their audience in the Scramble tag: name it `<Audience> - <Feature>` (`Public - Blog`, `User - Blog`) so the docs list the bands in audience order instead of mixing public and user endpoints under one feature tag.

```php
namespace App\Http\Controllers\Api\V1\Public;

use Dedoc\Scramble\Attributes\Group;

#[Group('Public - Memorials', weight: 10)]
class PetMemorialController extends Controller
```

## Return responses via the ApiResponse trait
Controllers use `App\Traits\ApiResponse` and respond only through its helpers: `success()`, `created()`, `error()`, `notFound()`, `unauthorized()`, `forbidden()`, `validationError()`. Keep the envelope `{ success, message, data }` (with `errors` added when present) on every response, including actions that return no data such as delete and logout (HTTP 200, `data: null`). Never call `response()->json()` directly in a controller.

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

        return $this->success(null, 'Post deleted successfully');
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

## Order API doc tags with #[Group] weight
Scramble orders document tags by ascending `#[Group('Name', weight: N)]` weight (default is `PHP_INT_MAX`, so weightless groups sort last). Name each tag `<Audience> - <Feature>` and give every audience one weight band so the docs always read Auth, then Public, then User, then Admin, and future groups slot in without renumbering: Auth `1`, Public `10–39`, User `40–99`, Admin `100–200`. Current values: `Auth` 1; `Public - Memorials` 10, `Public - Blog` 20, `Public - Shop` 30; `User - Memorials` 40, `User - Blog` 50, `User - Shop` 60, `User - Comments` 70, `User - Likes` 80; `Admin - User` 100, `Admin - Dashboard` 110, `Admin - Blog` 120, `Admin - Blog Category` 130, `Admin - Shop` 140. Cross-content endpoints (the delete-by-type routes) take the tag of the audience file that registers them plus the resource they address (`User - Comments`, `User - Likes`). Controllers sharing one tag must all use the same weight — the first non-null weight Scramble encounters for a tag wins.
