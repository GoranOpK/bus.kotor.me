<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append([
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\TrustProxies::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
            \Illuminate\Http\Middleware\ValidatePathEncoding::class,
            \Illuminate\Foundation\Http\Middleware\InvokeDeferredCallbacks::class,
            \Illuminate\Foundation\Http\Middleware\TransformsRequest::class,
        ]);



        $middleware->web([
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->api([
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'prevent.readonly' => \App\Http\Middleware\PreventReadonlyAdmin::class,
            'custom.auth' => \App\Http\Middleware\AuthenticateCustom::class,
            'admin' => \App\Http\Middleware\AuthorizeAdmin::class,
            'admin.or.readonly' => \App\Http\Middleware\AuthorizeAdminOrControl::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Konfiguracija za autentifikaciju - ne redirect-uj na login rutu
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }
        });
    })
    ->withSchedule(function (Schedule $schedule) {
        // Dnevni finansijski izvještaj – svaki dan u 07:30
        $schedule->command('reports:daily-finance')->dailyAt('07:30');

        // Mjesečni finansijski izvještaj – 1. u mjesecu u 07:30 (za prethodni mjesec)
        $schedule->command('reports:monthly-finance')->monthlyOn(1, '07:30');

        // Godišnji finansijski izvještaj – 1. januara u 07:30 (za prethodnu godinu)
        $schedule->command('reports:yearly-finance')->yearlyOn(1, 1, '07:30');

        // Dnevni izvještaj o rezervacijama po tipu vozila – svaki dan u 07:30
        $schedule->command('reports:daily-vehicle-reservations')->dailyAt('07:30');

        // Mjesečni izvještaj o rezervacijama po tipu vozila – 1. u mjesecu u 07:30 (za prethodni mjesec)
        $schedule->command('reports:monthly-vehicle-reservations')->monthlyOn(1, '07:30');

        // Godišnji izvještaj o rezervacijama po tipu vozila – 1. januara u 07:30 (za prethodnu godinu)
        $schedule->command('reports:yearly-vehicle-reservations')->yearlyOn(1, 1, '07:30');
    })
    ->create();