<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Http\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'verification.required' => \App\Http\Middleware\VerificationRequired::class,
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'maintenance.check' => \App\Http\Middleware\CheckMaintenanceMode::class,
            'announcements' => \App\Http\Middleware\CheckUserAnnouncements::class,
            'impersonation' => \App\Http\Middleware\ImpersonationMiddleware::class,
            '2fa' => \App\Http\Middleware\TwoFactorAuthentication::class,
            'password.change' => \App\Http\Middleware\CheckPasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle 404 errors
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Resource not found'
                ], 404);
            }

            return response()->view('errors.404', [], 404);
        });

        // Handle 419 errors (CSRF token mismatch)
        $exceptions->render(function (TokenMismatchException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'CSRF token mismatch'
                ], 419);
            }

            return response()->view('errors.419', [], 419);
        });

        // Handle 403 errors (Forbidden)
        $exceptions->render(function (AccessDeniedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Access denied'
                ], 403);
            }

            return response()->view('errors.403', [], 403);
        });

        // Handle 503 errors (Service Unavailable)
        $exceptions->render(function (ServiceUnavailableHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Service temporarily unavailable'
                ], 503);
            }

            return response()->view('errors.503', [], 503);
        });

    })->create();