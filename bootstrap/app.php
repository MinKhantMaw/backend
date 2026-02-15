<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use App\Support\ApiResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'ensure_role' => App\Http\Middleware\EnsureUserHasRole::class,
            'verify_webhook_signature' => App\Http\Middleware\VerifyWebhookSignature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error('Validation failed.', 422, $e->errors());
            }
        });

        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error('Unauthenticated.', 401);
            }
        });

        $exceptions->render(function (AuthorizationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error('Forbidden.', 403);
            }
        });

        $exceptions->render(function (ModelNotFoundException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return ApiResponse::error('Resource not found.', 404);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $status = $e->getStatusCode();
                return ApiResponse::error($e->getMessage() ?: 'Request failed.', $status);
            }
        });

        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                report($e);
                return ApiResponse::error('Something went wrong.', 500);
            }
        });
    })->create();
