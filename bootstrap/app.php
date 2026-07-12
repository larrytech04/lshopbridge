<?php

use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureKycLevel;
use App\Http\Middleware\EnsureUserRole;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Stateless provider webhooks (no session, no CSRF).
            Route::prefix('webhooks')
                ->name('webhooks.')
                ->group(base_path('routes/webhooks.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserRole::class,
            'active' => EnsureActiveAccount::class,
            'kyc' => EnsureKycLevel::class,
        ]);

        // Every authenticated web request is checked for a usable account.
        $middleware->appendToGroup('web', EnsureActiveAccount::class);

        // Apply the visitor's chosen UI language on every request.
        $middleware->appendToGroup('web', SetLocale::class);

        // Provider webhooks are verified by signature, not CSRF tokens.
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
