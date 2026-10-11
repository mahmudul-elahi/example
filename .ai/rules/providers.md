---
paths:
  - 'app/Providers/**'
---

# Providers

## Disable default Resource wrapping
Call JsonResource::withoutWrapping() in AppServiceProvider::boot() so API Resources don't add their own top-level data key — the ApiResponse envelope already provides one.

## Example

`app/Providers/AppServiceProvider.php`

```php
namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        JsonResource::withoutWrapping();
    }
}
```

Without this, `new PostResource($post)` would render as `data: { data: { ... } }`.
