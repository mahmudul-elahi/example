---
paths:
  - 'bootstrap/**'
---

# Bootstrap

## Render all exceptions as the API envelope
Register JSON error rendering in bootstrap/app.php withExceptions(): ModelNotFoundException/NotFoundHttpException -> 404, AuthenticationException -> 401, AccessDeniedHttpException/AuthorizationException -> 403, ValidationException -> 422, everything else -> 500. Every error uses the same { success: false, message, errors } shape as ApiResponse. Controllers never try/catch these.

## Example

`bootstrap/app.php`

```php
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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

        $exceptions->render(function (Throwable $e, Request $request) {
            $status = match (true) {
                $e instanceof ValidationException => Response::HTTP_UNPROCESSABLE_ENTITY,
                $e instanceof AuthenticationException => Response::HTTP_UNAUTHORIZED,
                $e instanceof AuthorizationException => Response::HTTP_FORBIDDEN,
                $e instanceof ModelNotFoundException => Response::HTTP_NOT_FOUND,
                default => Response::HTTP_INTERNAL_SERVER_ERROR,
            };

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e instanceof ValidationException ? $e->errors() : [],
            ], $status);
        });
    })->create();
```
