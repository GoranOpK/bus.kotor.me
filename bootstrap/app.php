<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
<<<<<<< HEAD
use Illuminate\Console\Scheduling\Schedule;
=======
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use App\Http\Middleware\EncryptCookies;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use App\Http\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Auth\Middleware\Authorize;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\PreventReadonlyAdmin;
use App\Http\Middleware\AuthenticateCustom;
use App\Http\Middleware\AuthorizeAdmin;
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
<<<<<<< HEAD
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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
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
=======
        // GLOBAL middleware (za sve zahteve)
        $middleware->append([
            HandleCors::class,
            TrustProxies::class,
            PreventRequestsDuringMaintenance::class,
            ValidatePostSize::class,
            TrimStrings::class,
            ConvertEmptyStringsToNull::class,
        ]);

        // WEB middleware grupa (samo za web.php rute)
        $middleware->web([
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            // EnsureFrontendRequestsAreStateful::class, // Samo ako koristiš SPA cookie auth (Sanctum), inače izostavi!
        ]);

        // API middleware grupa (samo za api.php rute)
        $middleware->api([
            'throttle:api',
            SubstituteBindings::class,
            // EnsureFrontendRequestsAreStateful::class, // Samo ako koristiš SPA cookie auth (Sanctum), inače izostavi!
        ]);

        // Route middleware aliasi
        $middleware->alias([
            'auth'             => Authenticate::class,
            'auth.basic'       => AuthenticateWithBasicAuth::class,
            'auth.session'     => AuthenticateSession::class,
            'cache.headers'    => SetCacheHeaders::class,
            'can'              => Authorize::class,
            'guest'            => RedirectIfAuthenticated::class,
            'password.confirm' => RequirePassword::class,
            'signed'           => ValidateSignature::class,
            'throttle'         => ThrottleRequests::class,
            'verified'         => EnsureEmailIsVerified::class,
            'prevent.readonly' => PreventReadonlyAdmin::class,
            'custom.auth'      => AuthenticateCustom::class,
            'admin'            => AuthorizeAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
