<?php

declare(strict_types=1);

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
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
        $middleware->redirectGuestsTo(null);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
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

            $payload = [
                'success' => false,
                'message' => $message,
            ];

            if ($e instanceof ValidationException) {
                $payload['errors'] = $e->errors();
            }

            return response()->json($payload, $status);
        });
    })
    ->prefersJsonResponses()
    ->create();
