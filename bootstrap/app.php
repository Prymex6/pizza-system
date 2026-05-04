<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/landlord.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Ufaj proxy (Cloudflare/Apache) — bez X-Forwarded-Host żeby subdomeny tenantów działały
        $middleware->trustProxies(
            at: '*',
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT,
        );
        $middleware->prepend(\App\Http\Middleware\ForceHttps::class);
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        // Exclude payment webhooks and public contact form from CSRF verification
        $middleware->validateCsrfTokens(except: [
            '*/payment/webhook',
            '*/payment/webhook/*',
            'admin/broadcasting/auth',
            '*/broadcasting/auth',
            '/contact',
        ]);

        $middleware->alias([
            'auth.super_admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'auth.tenant' => \App\Http\Middleware\EnsureTenantAuth::class,
            'auth.customer' => \App\Http\Middleware\EnsureCustomerAuth::class,
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'check.setup' => \App\Http\Middleware\Tenant\CheckSetupComplete::class,
            'check.license' => \App\Http\Middleware\Tenant\CheckTenantLicense::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Zamień 429 na polską wiadomość — dla Inertia i JSON (axios)
        $exceptions->render(function (
            \Illuminate\Http\Exceptions\ThrottleRequestsException $e,
            \Illuminate\Http\Request $request
        ) {
            $message = 'Zbyt wiele prób. Poczekaj chwilę i spróbuj ponownie.';
            if ($request->inertia()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'throttle' => $message,
                ]);
            }
            return response()->json(['message' => $message], 429);
        });
    })->create();
