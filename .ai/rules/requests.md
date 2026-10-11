---
paths:
  - 'app/Http/Requests/**'
---

# Requests

## Validate with Form Requests
All request validation lives in app/Http/Requests, suffixed Request. Type-hint the Form Request in the controller method. Never call `$request->validate()` or use the Validator facade inline — the Form Request is the single source of truth for rules.

## Example

**Store** — `app/Http/Requests/StorePostRequest.php`

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'unique:posts,title'],
            'body' => ['required', 'string'],
        ];
    }
}
```

**Update** — `app/Http/Requests/UpdatePostRequest.php`

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255', Rule::unique('posts', 'title')->ignore($this->route('post'))],
            'body' => ['sometimes', 'string'],
        ];
    }
}
```

**Usage in a controller** — type-hint the request; validated data comes from `validated()`:

```php
public function store(StorePostRequest $request): JsonResponse
{
    return $this->created(new PostResource($this->posts->create($request->validated())));
}
```

## Authorize in Form Requests, authenticate with Sanctum
Protect routes with Sanctum middleware (auth:sanctum) in routes/api.php. Put per-action authorization in the Form Request's authorize() method (delegating to policies/gates); never gate inside the controller.

## Example

```php
namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('post'));
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'body' => ['sometimes', 'string'],
        ];
    }
}
```

A failed `authorize()` returns 403 (mapped in bootstrap/app.php); validation failure returns 422 automatically — both in the ApiResponse envelope.
