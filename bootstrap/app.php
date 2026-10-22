<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->alias(['role' => EnsureRole::class]);
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
        // Paystack posts server-to-server; authenticity is checked by HMAC signature.
        $middleware->validateCsrfTokens(except: ['webhooks/paystack']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Before the web installer has run (shared hosting), show a calm holding page, not a 500.
        $exceptions->render(function (QueryException $e, Request $request) {
            if (filled(config('app.install_token')) && ! file_exists(storage_path('app/installed.lock')) && ! $request->is('install', 'install/*')) {
                return response()->view('setup-pending', [], 503);
            }
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

// Shared hosting (DirectAdmin/cPanel): the web root is public_html, not ./public.
// public_html/index.php defines FF85_PUBLIC_PATH; CLI falls back to a sibling public_html.
if (defined('FF85_PUBLIC_PATH')) {
    $app->usePublicPath(FF85_PUBLIC_PATH);
} elseif (! is_dir($app->basePath('public')) && is_dir(dirname($app->basePath()).'/public_html')) {
    $app->usePublicPath(dirname($app->basePath()).'/public_html');
}

return $app;
