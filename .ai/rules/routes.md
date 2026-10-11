---
paths:
  - 'routes/**'
---

# Routes

## API-only application
This project serves JSON APIs only. Register endpoints in routes/api.php (wired up in bootstrap/app.php) — never add Blade views, web routes, or `view()` responses. Every endpoint returns JSON.

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

**routes/api.php** — every model gets an API resource route:

```php
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;

Route::apiResource('posts', PostController::class);

// Explicit routes when you need to name them:
Route::get('posts/{post}', [PostController::class, 'show'])->name('posts.show');

// Route groups (auth, versioning, prefix):
Route::middleware('auth:sanctum')->prefix('v1')->group(function (): void {
    Route::apiResource('posts', PostController::class);
});
```

Controller methods therefore use implicit route model binding (`Post $post`).

## Mount versioned API route groups
Mount versioned groups in routes/api.php, e.g. Route::prefix('v1')->group(...) wrapping apiResource routes. Do not add unversioned API endpoints.

## Versioned example

`routes/api.php`

```php
use App\Http\Controllers\Api\V1\PostController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // Public routes
    Route::apiResource('posts', PostController::class)->only(['index', 'show']);

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::apiResource('posts', PostController::class)->except(['index', 'show']);
    });
});
```
