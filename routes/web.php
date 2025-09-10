<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\PaymentController;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;
use App\Http\Controllers\AdminReadonlyController;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use App\Models\TempData;

/*
|--------------------------------------------------------------------------
| WEB ROUTES
|--------------------------------------------------------------------------
| Ovdje su rute za HTML stranice, forme i admin panel (nije API!).
| API rute idu u routes/api.php!
|--------------------------------------------------------------------------
*/
Route::post('/csrf-debug', function () {
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
Route::match(['GET', 'POST'], '/payment/callback', [PaymentController::class, 'callback'])
    ->name('payment.callback')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
Route::get('/payment/success', [PaymentController::class, 'success'])->name('payment.success');
Route::get('/payment/cancel',  [PaymentController::class, 'cancel'])->name('payment.cancel');
Route::get('/payment/error',   [PaymentController::class, 'error'])->name('payment.error');

// Prikaz i slanje forme za podršku
Route::get('/podrska', [SupportController::class, 'showForm'])->name('support.form');
Route::post('/podrska', [SupportController::class, 'send'])->name('support.send');

// Sanctum CSRF ruta (automatski kod instalacije Sanctuma)
Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show']);

// Globalna 'login' ruta za Laravel auth redirects
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

// Alternative Sanctum route registration
Route::prefix('sanctum')->group(function () {
    Route::get('csrf-cookie', [CsrfCookieController::class, 'show']);
});

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
        
        // Dodaj još admin-only rute po potrebi
    });

    // Rute za izveštaje - direktna provera autentifikacije u kontroleru
    Route::get('reports/daily-finance', [ReportController::class, 'sendDailyFinance']);
    Route::get('reports/daily-vehicle-reservations', [ReportController::class, 'sendDailyVehicleReservations']);
    Route::get('reports/monthly-finance', [ReportController::class, 'sendMonthlyFinance']);
    Route::get('reports/monthly-vehicle-reservations', [ReportController::class, 'sendMonthlyVehicleReservations']);
    Route::get('reports/yearly-finance', [ReportController::class, 'sendYearlyFinance']);
    Route::get('reports/yearly-vehicle-reservations', [ReportController::class, 'sendYearlyVehicleReservations']);

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

// ======= TEST RUTE (ukloni u produkciji!) =======
// Test ruta za PDF preview
Route::get('/test-pdf/{id}', function($id) {
    $reservation = \App\Models\Reservation::find($id);
    if (!$reservation) {
        return 'Rezervacija nije pronađena';
    }
    
    $controller = new \App\Http\Controllers\ReservationController(app(\App\Services\SlotService::class));
    // Uzmi jezik iz temp podataka ako postoji, inače koristi default
    $userLanguage = 'en'; // default
    if ($reservation->merchant_transaction_id) {
        $temp = \App\Models\TempData::where('merchant_transaction_id', $reservation->merchant_transaction_id)->first();
        if ($temp && $temp->user_language) {
            $userLanguage = $temp->user_language;
        }
    }
    
    try {
        $pdf = $controller->generateInvoicePdf($reservation, $userLanguage);
    } catch (\Exception $e) {
        \Log::error('Greška pri generisanju test PDF-a: ' . $e->getMessage());
        // Fallback na default jezik
        $pdf = $controller->generateInvoicePdf($reservation, 'en');
    }
    
    $filename = 'invoice-' . $reservation->id . '-' . date('Y-m-d', strtotime($reservation->reservation_date)) . '.pdf';
    
    return response($pdf)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
})->name('test.pdf');

// Ruta za besplatnu potvrdu
Route::get('/free-reservation-confirmation/{id}', function($id) {
    $reservation = \App\Models\Reservation::find($id);
    if (!$reservation) {
        return 'Rezervacija nije pronađena';
    }
    
    // Proveri da li je test parametar prisutan
    $isTest = request()->has('test');
    
    // Proveri da li je rezervacija zaista besplatna
    $isFreeReservation = $reservation->status === 'free';
    
    // Ako je test, preskoči proveru besplatnosti
    if (!$isFreeReservation && !$isTest) {
        return 'Ova rezervacija nije besplatna';
    }
    
    try {
        $pdf = \PDF::loadView('pdfs.free_reservation_confirmation', compact('reservation'));
        $filename = 'free-confirmation-' . $reservation->id . '-' . date('Y-m-d', strtotime($reservation->reservation_date)) . '.pdf';
        
        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    } catch (\Exception $e) {
        \Log::error('Greška pri generisanju besplatne potvrde: ' . $e->getMessage());
        return 'Greška pri generisanju potvrde';
    }
})->name('free.reservation.confirmation');

// Ruta za proveru da li rezervacija postoji
Route::get('/api/check-reservation/{merchantTransactionId}', function($merchantTransactionId) {
    $reservation = \App\Models\Reservation::where('merchant_transaction_id', $merchantTransactionId)->first();
    return response()->json([
        'exists' => $reservation ? true : false,
        'reservation_id' => $reservation ? $reservation->id : null
    ]);
})->name('api.check.reservation');

// Ruta za ručno slanje email-a
Route::post('/send-invoice-manually/{id}', function($id) {
    $controller = new \App\Http\Controllers\ReservationController(app(\App\Services\SlotService::class));
    return $controller->sendInvoiceManually($id);
})->name('send.invoice.manually');

// Ruta za download invoice PDF-a
Route::get('/download-invoice/{id}', function($id) {
    $reservation = \App\Models\Reservation::find($id);
    if (!$reservation) {
        return response('Rezervacija nije pronađena', 404);
    }
    
    // SIGURNOST: Proveri da li je ova rezervacija vezana za trenutnu sesiju
    // Koristi merchant_transaction_id kao što se koristi za mail-ove
    $sessionMerchantTxId = session('last_merchant_transaction_id');
    
    $isAuthorized = false;
    
    // Proveri da li je merchant_transaction_id u sesiji i da se poklapa
    if ($sessionMerchantTxId && $reservation->merchant_transaction_id == $sessionMerchantTxId) {
        $isAuthorized = true;
        \Log::info('Invoice download authorized by merchant_transaction_id', [
            'requested_id' => $id,
            'merchant_transaction_id' => $sessionMerchantTxId
        ]);
    }
    
    // DODATNA SIGURNOST: Proveri temp_data ako postoji
    if (!$isAuthorized && $sessionMerchantTxId) {
        $temp = \App\Models\TempData::where('merchant_transaction_id', $sessionMerchantTxId)
            ->whereIn('status', ['available', 'reserved', 'failed'])
            ->first();
        if ($temp) {
            // Proveri da li se podaci poklapaju
            if ($temp->email === $reservation->email && 
                $temp->license_plate === $reservation->license_plate &&
                $temp->reservation_date === $reservation->reservation_date) {
                $isAuthorized = true;
                \Log::info('Invoice download authorized by temp_data match', [
                    'requested_id' => $id,
                    'merchant_transaction_id' => $sessionMerchantTxId,
                    'temp_id' => $temp->id
                ]);
            }
        }
    }
    
    // DODATNA PROVERA: Ako je rezervacija kreirana u poslednjih 2 minuta, dozvoli pristup
    if (!$isAuthorized && $reservation->created_at->diffInMinutes(now()) <= 2) {
        $isAuthorized = true;
        \Log::info('Invoice download authorized by time check (2 min)', [
            'requested_id' => $id,
            'created_at' => $reservation->created_at,
            'minutes_ago' => $reservation->created_at->diffInMinutes(now()),
            'reason' => 'Rezervacija kreirana u poslednjih 2 minuta'
        ]);
    }
    
    // TESTIRANJE: Ako postoji query parametar za testiranje
    if (!$isAuthorized && request()->query('test_mode') === 'true') {
        $isAuthorized = true;
        \Log::info('Invoice download authorized by test mode', [
            'requested_id' => $id,
            'test_mode' => true
        ]);
    }
    
    if (!$isAuthorized) {
        \Log::warning('Unauthorized invoice download attempt', [
            'requested_id' => $id,
            'session_merchant_tx_id' => $sessionMerchantTxId,
            'reservation_merchant_tx_id' => $reservation->merchant_transaction_id,
            'client_ip' => request()->ip(),
            'created_at' => $reservation->created_at
        ]);
        
        return response('Unauthorized access', 403);
    }
    
    $controller = new \App\Http\Controllers\ReservationController(app(\App\Services\SlotService::class));
    // Postavi default jezik pošto smo odustali od višejezičnosti
    $userLanguage = 'en';
    
    try {
        $pdf = $controller->generateInvoicePdf($reservation, $userLanguage);
    } catch (\Exception $e) {
        \Log::error('Greška pri generisanju download PDF-a: ' . $e->getMessage());
        // Fallback na default jezik
        $pdf = $controller->generateInvoicePdf($reservation, 'en');
    }
    
    $filename = 'invoice-' . $reservation->id . '-' . date('Y-m-d', strtotime($reservation->reservation_date)) . '.pdf';
    
    return response($pdf)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
})->name('download.invoice');

// ======= GLOBAL GET catch-all (SPA podrška) =======
// Catch-all za GET (SPA)
Route::get('/{any}', function () {
    $path = public_path('index.html');
    if (!file_exists($path)) {
        abort(404, 'index.html nije pronađen!');
    }
    return response()->file($path);
})->where('any', '^(?!api\/).*');

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
    return response('Route not found', 404);
});