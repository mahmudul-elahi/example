---
paths:
  - 'bootstrap/**'
---

# Bootstrap

## Render all exceptions as the API envelope
Register JSON error rendering in bootstrap/app.php withExceptions(): ModelNotFoundException/NotFoundHttpException -> 404, AuthenticationException -> 401, AccessDeniedHttpException/AuthorizationException -> 403, ValidationException -> 422, other `HttpExceptionInterface` exceptions keep their real status code, everything else -> 500. Every error uses the `{ success: false, message }` shape, with `errors` added only for validation failures. Controllers never try/catch these.

## Never leak internal messages
Only expose `$e->getMessage()` for expected HTTP exceptions (validation, authentication, access, 404, other `HttpExceptionInterface`). For unexpected 500s return a generic `Server Error` — or the raw message only when `config('app.debug')` is true — otherwise SQL, file paths, and model names leak to clients. `ModelNotFoundException` always returns a generic "Resource not found".

## API-only routing
Register only `api`, `commands`, and `health` in `withRouting()`. This application has no `web` routes (`.ai/rules/routes.md`), so do not boot the `web` middleware group.

## Example

`bootstrap/app.php`

```php
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (Throwable $e, Request $request): JsonResponse {
            [$status, $message] = match (true) {
                $e instanceof ValidationException => [Response::HTTP_UNPROCESSABLE_ENTITY, $e->getMessage()],
                $e instanceof AuthenticationException => [Response::HTTP_UNAUTHORIZED, $e->getMessage()],
                $e instanceof AccessDeniedHttpException,
                $e instanceof AuthorizationException => [Response::HTTP_FORBIDDEN, $e->getMessage()],
                $e instanceof ModelNotFoundException => [Response::HTTP_NOT_FOUND, 'Resource not found'],
                $e instanceof NotFoundHttpException => [Response::HTTP_NOT_FOUND, $e->getMessage()],
                $e instanceof HttpExceptionInterface => [$e->getStatusCode(), $e->getMessage()],
                default => [Response::HTTP_INTERNAL_SERVER_ERROR, config('app.debug') ? $e->getMessage() : 'Server Error'],
            };

            $payload = ['success' => false, 'message' => $message];

            if ($e instanceof ValidationException) {
                $payload['errors'] = $e->errors();
            }

            return response()->json($payload, $status);
        });
    })->create();
```
