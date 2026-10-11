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

## Define named rate limiters here
Register named rate limiters for public endpoints in `AppServiceProvider::boot()` with `RateLimiter::for()`. Keep the per-minute values in `config/auth.php` (`auth.rate_limits`) rather than hardcoding them, so they stay tunable per environment and overridable in tests.

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    JsonResource::withoutWrapping();

    RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(
        (int) config('auth.rate_limits.login'),
    )->by($request->input('email').'|'.$request->ip()));
}
```

Apply them from routes with `->middleware('throttle:login')` (`.ai/rules/routes.md`).
