<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\PaymentController;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;
use App\Http\Controllers\AdminReadonlyController;

/*
|--------------------------------------------------------------------------
| WEB ROUTES
|--------------------------------------------------------------------------
| Ovdje su rute za HTML stranice, forme i admin panel (nije API!).
| API rute idu u routes/api.php!
|--------------------------------------------------------------------------
*/
Route::post('/csrf-debug', function () {
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
Route::post('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');
Route::get('/payment/success', [PaymentController::class, 'success'])->name('payment.success');
Route::get('/payment/cancel',  [PaymentController::class, 'cancel'])->name('payment.cancel');
Route::get('/payment/error',   [PaymentController::class, 'error'])->name('payment.error');

// Prikaz i slanje forme za podršku
Route::get('/podrska', [SupportController::class, 'showForm'])->name('support.form');
Route::post('/podrska', [SupportController::class, 'send'])->name('support.send');

// === DODAJ POSEBNU RUTU ZA CSRF TOKEN (AJAX friendly) ===
//Route::get('/csrf-token', function () {
//	 \Log::info('CSRF TEST ROUTE REACHED');
//    return response()->json(['csrf_token' => csrf_token()]);
// });

// Sanctum CSRF ruta (automatski kod instalacije Sanctuma)
Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show']);

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
        Route::get('izvjestaj', [ReportController::class, 'generate'])->name('admin.report');
        Route::post('brisanje', [ReservationController::class, 'delete'])->name('admin.brisanje');
        // Dodaj još admin-only rute po potrebi
    });

    // TEST/DEV rute - samo lokalno okruženje
    if (app()->environment('local')) {
        Route::get('test-dnevni-finansijski', [ReportController::class, 'sendDailyFinance']);
        Route::get('test-dnevni-vozila', [ReportController::class, 'sendDailyVehicleReservations']);
        Route::get('test-mjesecni-finansijski', [ReportController::class, 'sendMonthlyFinance']);
        Route::get('test-mjesecni-vozila', [ReportController::class, 'sendMonthlyVehicleReservations']);
        Route::get('test-godisnji-finansijski', [ReportController::class, 'sendYearlyFinance']);
        Route::get('test-godisnji-vozila', [ReportController::class, 'sendYearlyVehicleReservations']);
        Route::get('test-payment', [PaymentController::class, 'test']);
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

// ======= GLOBAL GET catch-all (SPA podrška) =======
// Catch-all za GET (SPA)
Route::get('/{any}', function () {
    $path = public_path('index.html');
    if (!file_exists($path)) {
        abort(404, 'index.html nije pronađen!');
    }
    return response()->file($path);
})->where('any', '^(?!api\/).*');

// Fallback za ostalo
Route::fallback(function () {
    \Log::info('FALLBACK route triggered! Method: ' . request()->method() . ', URI: ' . request()->path());
    return response('Route not found', 404);
});