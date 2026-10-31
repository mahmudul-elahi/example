---
paths:
  - 'routes/**'
---

# Routes

## API-only application
This project serves JSON APIs only. Register endpoints in the matching routes/v1/*.php file (mounted from routes/api.php, wired up in bootstrap/app.php) — never add Blade views, web routes, or `view()` responses. Every endpoint returns JSON.

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

**routes/v1/public.php** — guest-readable resource and single-action routes (loaded from routes/api.php):

```php
use App\Http\Controllers\Api\V1\Public\PostController;
use Illuminate\Support\Facades\Route;

Route::apiResource('posts', PostController::class)->only(['index', 'show']);

// Explicit single-action routes:
Route::get('posts/{post}', [PostController::class, 'show']);
```

**routes/v1/user.php** — wrap authenticated endpoints in the `auth:sanctum` group:

```php
use App\Http\Controllers\Api\V1\User\PostController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('posts', PostController::class)->except(['index', 'show']);
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
Keep routes/api.php as the only route entrypoint: it mounts each version prefix and loads the four v1 audience files. Do not add unversioned API endpoints, and do not inline endpoint routes in routes/api.php.

`routes/api.php`

```php
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group([
    __DIR__.'/v1/auth.php',
    __DIR__.'/v1/public.php',
    __DIR__.'/v1/user.php',
]);

Route::prefix('v1/admin')
    ->middleware(['auth:sanctum', 'role:admin'])
    ->group([
        __DIR__.'/v1/admin.php',
    ]);
```

## File routes by audience, not by model
The four v1 files mirror the controller subfolders: `auth.php` (auth feature), `public.php` (no auth), `user.php` (`auth:sanctum`), and `admin.php` (admin-only). Spread one model's routes across the files by audience instead of creating one file per feature — a `memorials` resource keeps `index`/`show` in `public.php` and `store`/`update`/`destroy` in `user.php`.

`routes/v1/public.php`

```php
use App\Http\Controllers\Api\V1\Public\PetMemorialController;
use Illuminate\Support\Facades\Route;

Route::apiResource('memorials', PetMemorialController::class)->only(['index', 'show']);
```

`routes/v1/user.php`

```php
use App\Http\Controllers\Api\V1\User\PetMemorialController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('memorials', PetMemorialController::class)->except(['index', 'show']);
});
```

Add a new endpoint to the audience file that matches its middleware (`public.php`, `user.php`, `auth.php`, or `admin.php`); do not create a route file per model.

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

## Direct POST routes for updates with file uploads
PHP only populates `$_FILES` and parses `multipart/form-data` natively on HTTP `POST` requests. When a resource allows updating with file/image uploads, register an explicit direct `POST` route alongside the resource route so clients can upload files directly without needing `_method` spoofing:

```php
Route::apiResource('memorials', PetMemorialController::class)->except(['show']);
Route::post('memorials/{memorial}', [PetMemorialController::class, 'update']);
```
