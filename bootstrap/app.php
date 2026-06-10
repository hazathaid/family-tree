<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $exception->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        });

        $exceptions->render(function (AuthenticationException $exception, $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'errors' => [],
            ], Response::HTTP_UNAUTHORIZED);
        });

        $exceptions->render(function (Throwable $exception, $request) {
            if (! $request->expectsJson() || config('app.debug')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Error',
                'errors' => [],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    })
    ->create();
