<?php

use App\Http\Middleware\EnsureTwoFactorSatisfied;
use App\Http\Middleware\RequireAdminPermission;
use App\Http\Middleware\RequireProjectReadPermission;
use App\Http\Middleware\RequireProjectWritePermission;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Spatie\LaravelFlare\Facades\Flare;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'require.admin' => RequireAdminPermission::class,
            'require.project.read' => RequireProjectReadPermission::class,
            'require.project.write' => RequireProjectWritePermission::class,
        ]);

        $middleware->web(append: [
            EnsureTwoFactorSatisfied::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Flare::handles($exceptions);

        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            $message = 'The uploaded file is too large. Maximum allowed file size is 55 MB.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 413);
            }

            return redirect()->back()->with('error', $message);
        });
    })->create();
