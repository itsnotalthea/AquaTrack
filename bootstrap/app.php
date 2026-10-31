<?php

use App\Http\Middleware\RoleMiddleware;
use App\Support\DatabaseFailure;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (Throwable $exception, Request $request): ?Response {
            if (! DatabaseFailure::isUnavailable($exception)) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => DatabaseFailure::message()], 503);
            }

            return response()->view('errors.database-unavailable', [], 503);
        });
    })->create();
