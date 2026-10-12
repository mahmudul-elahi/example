---
paths:
  - 'routes/**'
---

# Routes

## API-only application
This project serves JSON APIs only. Register endpoints in the matching routes/v1/{feature}.php file (mounted from routes/api.php, wired up in bootstrap/app.php) — never add Blade views, web routes, or `view()` responses. Every endpoint returns JSON.

## Example

**bootstrap/app.php** — register the API routes file:

```php
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
```

**routes/v1/posts.php** — every model gets an API resource route (loaded from routes/api.php):

```php
use App\Http\Controllers\Api\V1\PostController;
use Illuminate\Support\Facades\Route;

Route::apiResource('posts', PostController::class);

// Explicit single-action routes:
Route::get('posts/{post}', [PostController::class, 'show']);

// Route groups (auth, middleware, prefix):
Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('posts', PostController::class);
});
```

Controller methods therefore use implicit route model binding (`Post $post`).

## Do not name API routes
This is an API-only application: there is no Blade, redirect, or `route()` call that generates URLs, so route names serve no purpose. Never add `->name()` to a route in routes/v1/*.php — the HTTP method + URI is the contract. Named routes are reserved for URL generation between pages, which this app does not have.

```php
// Correct
Route::post('login', [AuthController::class, 'login']);

// Wrong — no consumer resolves route names in an API
Route::post('login', [AuthController::class, 'login'])->name('auth.login');
```

## Mount versioned API route groups
Keep routes/api.php as the only route entrypoint: it mounts each version prefix and loads one file per feature from the matching routes/v1/ folder. Do not add unversioned API endpoints, and do not inline feature routes in routes/api.php.

`routes/api.php`

```php
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group([
    __DIR__.'/v1/auth.php',
]);
```

`routes/v1/auth.php`

```php
use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
});
```

Create a new routes/v1/{feature}.php file per feature and append it to the version's file list in routes/api.php. Group a feature's related routes (e.g. auth, password reset, email verification) inside that one file.

## Versioned example

`routes/v1/posts.php`

```php
use App\Http\Controllers\Api\V1\PostController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::apiResource('posts', PostController::class)->only(['index', 'show']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('posts', PostController::class)->except(['index', 'show']);
});
```

## Protect admin endpoints with the role middleware
Admin-only endpoints combine Sanctum auth with the `role` middleware alias. Only `Roles` enum values are valid arguments (`admin` | `user`), and users hold a single role.

```php
Route::middleware(['auth:sanctum', 'role:admin'])->group(function (): void {
    Route::apiResource('posts', PostController::class);
});
```

Use `role_or_permission:admin,user` for OR semantics. Prefer per-action authorization in the Form Request's `authorize()` (`.ai/rules/requests.md`); the role middleware protects whole groups.

## Throttle the public auth endpoints
Login and registration are unauthenticated, so protect them with the named rate limiters defined in AppServiceProvider (`login` keyed by email + IP, `register` keyed by IP). Limits come from `config('auth.rate_limits')` and are overridable with `AUTH_LOGIN_RATE_LIMIT` / `AUTH_REGISTER_RATE_LIMIT`.

```php
Route::post('register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
```

A throttled request returns 429 in the standard error envelope. Raise the limit env vars when load/stress testing, which otherwise stops at the per-minute cap.
