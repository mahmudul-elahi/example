<?php

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
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
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
            $status = match (true) {
                $e instanceof ValidationException => Response::HTTP_UNPROCESSABLE_ENTITY,
                $e instanceof AuthenticationException => Response::HTTP_UNAUTHORIZED,
                $e instanceof AccessDeniedHttpException,
                $e instanceof AuthorizationException => Response::HTTP_FORBIDDEN,
                $e instanceof NotFoundHttpException,
                $e instanceof ModelNotFoundException => Response::HTTP_NOT_FOUND,
                $e instanceof HttpExceptionInterface => $e->getStatusCode(),
                default => Response::HTTP_INTERNAL_SERVER_ERROR,
            };

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e instanceof ValidationException ? $e->errors() : [],
            ], $status);
        });
    })->create();
