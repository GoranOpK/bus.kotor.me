<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\PaymentController;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;
use App\Http\Controllers\AdminReadonlyController;
<<<<<<< HEAD
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
=======
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb

/*
|--------------------------------------------------------------------------
| WEB ROUTES
|--------------------------------------------------------------------------
| Ovdje su rute za HTML stranice, forme i admin panel (nije API!).
| API rute idu u routes/api.php!
|--------------------------------------------------------------------------
*/
Route::post('/csrf-debug', function () {
<<<<<<< HEAD
    file_put_contents(storage_path('debug.txt'), now() . " CSRF DEBUG\n", FILE_APPEND);
    return response()->json(['ok' => true])->header('X-DEMO', 'csrf-debug-match');
});

// Modified CSRF debug route using GET (since POST is blocked by Apache)
Route::get('/csrf-debug', function () {
    file_put_contents(storage_path('debug.txt'), now() . " CSRF DEBUG GET\n", FILE_APPEND);
    return response()->json([
        'ok' => true, 
        'csrf_token' => csrf_token(),
        'session_id' => session()->getId(),
        'cookies' => $_COOKIE
    ])->header('X-DEMO', 'csrf-debug-get-match');
});

// Test POST route without CSRF protection
Route::post('/test-post', function () {
    return response()->json(['status' => 'POST works', 'time' => now()]);
});

// Simple test route without any middleware
Route::post('/test-simple', function () {
    return response()->json(['status' => 'Simple POST works', 'time' => now()]);
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// GET version of CSRF debug for testing
Route::get('/csrf-debug-get', function () {
    file_put_contents(storage_path('debug.txt'), now() . " CSRF DEBUG GET\n", FILE_APPEND);
    return response()->json(['ok' => true, 'csrf_token' => csrf_token()])->header('X-DEMO', 'csrf-debug-get-match');
});

// Dodaj ovu rutu dole:
Route::match(['get', 'post'], '/test-session', function (Request $request) {
    return response()->json([
        'session_id' => $request->session()->getId(),
        'session' => $request->session()->all(),
        'cookies' => $_COOKIE,
        'token_in_request' => $request->input('_token'),
        'csrf_token_func' => csrf_token(),
    ]);
});

// Test route za proveru da li Laravel radi
Route::get('/test-laravel', function () {
    return response()->json(['status' => 'Laravel is working', 'time' => now()]);
});

// Test callback route
Route::match(['GET', 'POST'], '/test-callback', function (Request $request) {
    \Log::info('Test callback hit', [
        'method' => $request->method(),
        'url' => $request->fullUrl(),
        'headers' => $request->headers->all(),
        'body' => $request->getContent(),
    ]);
    return response()->json(['status' => 'test callback ok']);
=======
    \Log::info('CSRF DEBUG WEB route pogodjena!');
    return response()->json(['ok' => true])->header('X-DEMO', 'csrf-debug-match');
});

Route::get('/test-session', function() {
    $session_id = session()->getId();
    $session_data = session()->all();
    return [
        'session_id' => $session_id,
        'session_data' => $session_data,
        'cookies' => $_COOKIE
    ];
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
});

Route::get('/test-db', function() {
    try {
        \DB::table('sessions')->insert([
            'id' => uniqid(),
            'payload' => base64_encode(serialize(['test'=>'ok'])),
            'last_activity' => time()
        ]);
        return 'DB insert OK';
    } catch (\Exception $e) {
        return $e->getMessage();
    }
});

// ======= JAVNE KORISNIČKE RUTE =======

// Plaćanje (HPP redirect flow)
Route::post('/procesiraj-placanje', [PaymentController::class, 'redirectToHpp'])->name('payment.redirect-hpp');
<<<<<<< HEAD
Route::match(['GET', 'POST'], '/payment/callback', [PaymentController::class, 'callback'])
    ->name('payment.callback')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
=======
Route::post('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
Route::get('/payment/success', [PaymentController::class, 'success'])->name('payment.success');
Route::get('/payment/cancel',  [PaymentController::class, 'cancel'])->name('payment.cancel');
Route::get('/payment/error',   [PaymentController::class, 'error'])->name('payment.error');

// Prikaz i slanje forme za podršku
Route::get('/podrska', [SupportController::class, 'showForm'])->name('support.form');
Route::post('/podrska', [SupportController::class, 'send'])->name('support.send');

<<<<<<< HEAD
// Sanctum CSRF ruta (automatski kod instalacije Sanctuma)
Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show']);

// Alternative Sanctum route registration
Route::prefix('sanctum')->group(function () {
    Route::get('csrf-cookie', [CsrfCookieController::class, 'show']);
});
=======
// === DODAJ POSEBNU RUTU ZA CSRF TOKEN (AJAX friendly) ===
//Route::get('/csrf-token', function () {
//	 \Log::info('CSRF TEST ROUTE REACHED');
//    return response()->json(['csrf_token' => csrf_token()]);
// });

// Sanctum CSRF ruta (automatski kod instalacije Sanctuma)
Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show']);
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb

// ======= SPA KORISNIČKI FRONT (index.html) =======
Route::get('/', function () {
    $path = public_path('index.html');
    if (!file_exists($path)) {
        abort(404, 'index.html nije pronađen!');
    }
    return response()->file($path);
});

// ======= EKSPPLICITNE RUTE ZA FRONTEND HTML =======
Route::get('/admin-login.html', function () {
    $path = public_path('admin-login.html');
    if (!file_exists($path)) {
        abort(404, 'admin-login.html nije pronađen!');
    }
    return response()->file($path);
});

Route::get('/adminpanel.html', function () {
    $path = public_path('adminpanel.html');
    if (!file_exists($path)) {
        abort(404, 'adminpanel.html nije pronađen!');
    }
    return response()->file($path);
});

Route::get('/readonly-login.html', function () {
    $path = public_path('readonly-login.html');
    if (!file_exists($path)) {
        abort(404, 'readonly-login.html nije pronađen!');
    }
    return response()->file($path);
});

Route::get('/control.html', function () {
    $path = public_path('control.html');
    if (!file_exists($path)) {
        abort(404, 'control.html nije pronađen!');
    }
    return response()->file($path);
});

// ================== ADMIN PANEL SPA/AUTH LOGIKA ==================
Route::prefix('admin')->group(function () {
    // Admin login forma
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('admin.login');
    Route::post('login', [LoginController::class, 'login'])
        ->name('admin.login.submit')
        ->middleware('throttle:5,1');

    // Samo za ulogovane readonly admine
    Route::middleware(['auth:readonly'])->group(function () {
        // SPA za readonly admina (control.html)
        Route::get('readonly', function () {
            $path = public_path('control.html');
            if (!file_exists($path)) {
                abort(404, 'control.html nije pronađen!');
            }
            return response()->file($path);
        });
        // Primer API ruta za readonly
        Route::get('todays-reserved-slots', [AdminReadonlyController::class, 'todaysReservedSlots'])
            ->name('admin.todays_reserved_slots');
    });

    // Samo za ulogovane prave admine
    Route::middleware(['auth:admin'])->group(function () {
        // SPA za pravog admina (adminpanel.html)
        Route::get('panel', function () {
            $path = public_path('adminpanel.html');
            if (!file_exists($path)) {
                abort(404, 'adminpanel.html nije pronađen!');
            }
            return response()->file($path);
        });
        Route::post('logout', [LoginController::class, 'logout'])->name('admin.logout');
<<<<<<< HEAD
        
        // Dodaj još admin-only rute po potrebi
    });

    // Rute za izveštaje - direktna provera autentifikacije u kontroleru
    Route::get('reports/daily-finance', [ReportController::class, 'sendDailyFinance']);
    Route::get('reports/daily-vehicle-reservations', [ReportController::class, 'sendDailyVehicleReservations']);
    Route::get('reports/monthly-finance', [ReportController::class, 'sendMonthlyFinance']);
    Route::get('reports/monthly-vehicle-reservations', [ReportController::class, 'sendMonthlyVehicleReservations']);
    Route::get('reports/yearly-finance', [ReportController::class, 'sendYearlyFinance']);
    Route::get('reports/yearly-vehicle-reservations', [ReportController::class, 'sendYearlyVehicleReservations']);

=======
        Route::get('izvjestaj', [ReportController::class, 'generate'])->name('admin.report');
        Route::post('brisanje', [ReservationController::class, 'delete'])->name('admin.brisanje');
        // Dodaj još admin-only rute po potrebi
    });

>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
    // TEST/DEV rute - samo lokalno okruženje
    if (app()->environment('local')) {
        Route::get('test-payment', [PaymentController::class, 'test']);
        Route::get('test-fiskal', function() {
            $fiskalController = new \App\Http\Controllers\FiskalController();
            $testId = 'test-' . uniqid();
            
            // Test initDeposit
            $initResult = $fiskalController->initDeposit($testId);
            
            // Test fiscalization (this will fail without proper temp data, but we can test the structure)
            $fiscalResult = $fiskalController->fiscalization($testId);
            
            return response()->json([
                'initDeposit' => $initResult,
                'fiscalization' => $fiscalResult,
                'testId' => $testId
            ]);
        });
    }

    // CATCH-ALL ZA ADMIN PANEL SPA
    Route::get('/{any}', function () {
        // SPA rutiranje: ako je ulogovan admin vraća panel, readonly vraća control, ako nije - login
        if (auth('admin')->check()) {
            $path = public_path('adminpanel.html');
        } elseif (auth('readonly')->check()) {
            $path = public_path('control.html');
        } else {
            $path = public_path('admin-login.html');
        }
        if (!file_exists($path)) {
            abort(404, basename($path) . ' nije pronađen!');
        }
        return response()->file($path);
    })->where('any', '.*');
});

<<<<<<< HEAD
// ======= TEST RUTE (ukloni u produkciji!) =======
// Test ruta za PDF preview
Route::get('/test-pdf/{id}', function($id) {
    $reservation = \App\Models\Reservation::find($id);
    if (!$reservation) {
        return 'Rezervacija nije pronađena';
    }
    
    $controller = new \App\Http\Controllers\ReservationController(app(\App\Services\SlotService::class));
    $pdf = $controller->generateInvoicePdf($reservation);
    
    return response($pdf)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'inline; filename="test-invoice.pdf"');
})->name('test.pdf');

// Ruta za download invoice PDF-a
Route::get('/download-invoice/{id}', function($id) {
    $reservation = \App\Models\Reservation::find($id);
    if (!$reservation) {
        return response('Rezervacija nije pronađena', 404);
    }
    
    $controller = new \App\Http\Controllers\ReservationController(app(\App\Services\SlotService::class));
    $pdf = $controller->generateInvoicePdf($reservation);
    
    $filename = 'invoice-' . $reservation->id . '-' . date('Y-m-d') . '.pdf';
    
    return response($pdf)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
})->name('download.invoice');

=======
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
// ======= GLOBAL GET catch-all (SPA podrška) =======
// Catch-all za GET (SPA)
Route::get('/{any}', function () {
    $path = public_path('index.html');
    if (!file_exists($path)) {
        abort(404, 'index.html nije pronađen!');
    }
    return response()->file($path);
})->where('any', '^(?!api\/).*');

<<<<<<< HEAD
// Test route za proveru sesije (GET i POST)
Route::match(['get', 'post'], '/test-session-debug', function (Request $request) {
    return response()->json([
        'session_id' => session()->getId(),
        'session_exists' => session()->isStarted(),
        'cookies_sent' => $request->cookies->all(),
        'headers' => $request->headers->all(),
        'session_data' => session()->all(),
        'app_url' => config('app.url'),
        'session_secure' => config('session.secure'),
        'session_domain' => config('session.domain'),
        'session_path' => config('session.path'),
        'input' => $request->all(),
        'method' => $request->method(),
        'time' => now(),
    ]);
});

// Fallback za ostale metode (POST/PUT/DELETE...)
Route::fallback(function () {
    $method = request()->method();
    $uri = request()->path();
    \Log::warning("FALLBACK: $method $uri");
    // Za GET možeš vratiti index.html (ako baš želiš SPA fallback), ali NIKADA za POST/PUT/DELETE!
    if ($method === 'GET') {
        $path = public_path('index.html');
        if (file_exists($path)) {
            return response()->file($path);
        }
    }
=======
// Fallback za ostalo
Route::fallback(function () {
    \Log::info('FALLBACK route triggered! Method: ' . request()->method() . ', URI: ' . request()->path());
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
    return response('Route not found', 404);
});