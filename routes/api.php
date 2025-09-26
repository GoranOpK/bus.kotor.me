<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TimeSlotController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\VehicleTypeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\SystemConfigController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\Api\ReservedSlotsController;
use App\Http\Controllers\Api\ReadonlyAdminController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TempReservationController;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;
use Illuminate\Session\Middleware\StartSession;
use App\Models\SystemConfig;
use App\Http\Controllers\ReportController;


// GET test za sesiju
Route::middleware([StartSession::class])->group(function () {
    Route::get('/test-session-debug', function (Request $request) {
        return response()->json([
            'session_id' => session()->getId(),
            'session_exists' => session()->isStarted(),
            'cookies_sent' => $request->cookies->all(),
            'headers' => $request->headers->all(),
            'session_data' => session()->all(),
            'time' => now(),
        ]);
    });

    // Dodajem POST rutu za test-session-debug
    Route::post('/test-session-debug', function (Request $request) {
        return response()->json([
            'session_id' => session()->getId(),
            'session_exists' => session()->isStarted(),
            'cookies_sent' => $request->cookies->all(),
            'headers' => $request->headers->all(),
            'session_data' => session()->all(),
            'input' => $request->all(),
            'time' => now(),
        ]);
    });

    Route::post('/test-session', function (Request $request) {
        return response()->json([
            'session_id' => session()->getId(),
            'session_exists' => session()->isStarted(),
            'cookies_sent' => $request->cookies->all(),
            'headers' => $request->headers->all(),
            'session_data' => session()->all(),
            'input' => $request->all(),
            'time' => now(),
        ]);
    });

    Route::post('/procesiraj-placanje', [PaymentController::class, 'redirectToHpp']);
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Ovdje se definiraju sve API rute aplikacije.
*/

// ===== JAVNE API RUTE (bez autentifikacije) =====
Route::get('vehicle-types', [VehicleTypeController::class, 'index']);

Route::get('timeslots', [TimeSlotController::class, 'index']);
Route::get('timeslots/available', [TimeSlotController::class, 'availableSlots']);
Route::get('timeslots/reserved-today', [TimeSlotController::class, 'reservedSlotsToday']); // ZA KORISNIKA
Route::get('readonly-admin/timeslots/reserved-today', [ReservedSlotsController::class, 'reservedToday']); // SAMO ZA ADMIN PREGLED

// Slot rezervacija sistem
Route::post('reservations/reserve-slot', [ReservationController::class, 'reserveSlot'])->middleware('throttle:20,1');
Route::get('reservations/check-slot-reservation', [ReservationController::class, 'checkSlotReservation']);

// Readonly admin search reservations
Route::get('readonly-admin/search-reservations', function (\Illuminate\Http\Request $request) {
    $date = $request->query('date');
    $user_name = $request->query('user_name');
    $email = $request->query('email');
    $vehicle_type_id = $request->query('vehicle_type_id');
    $license_plate = $request->query('license_plate');
    
    $query = \App\Models\Reservation::with(['vehicleType']);
    
    if ($date) $query->where('reservation_date', $date);
    if ($user_name) $query->where('user_name', 'like', '%' . $user_name . '%');
    if ($email) $query->where('email', 'like', '%' . $email . '%');
    if ($vehicle_type_id) $query->where('vehicle_type_id', $vehicle_type_id);
    if ($license_plate) $query->where('license_plate', 'like', '%' . $license_plate . '%');
    
    return $query->orderBy('created_at', 'desc')->get();
})->middleware(['auth:sanctum', 'admin.or.readonly']);

// Readonly admin get single reservation
Route::get('readonly-admin/reservation/{id}', function ($id) {
    try {
        $reservation = \App\Models\Reservation::with(['vehicleType', 'dropOffTimeSlot', 'pickUpTimeSlot'])->find($id);
        if (!$reservation) {
            return response()->json(['error' => 'Reservation not found'], 404);
        }
        return $reservation;
    } catch (\Exception $e) {
        \Log::error('Error loading reservation: ' . $e->getMessage());
        return response()->json(['error' => 'Internal server error'], 500);
    }
})->middleware(['auth:sanctum', 'admin.or.readonly']);
Route::post('reservations/reserve', [ReservationController::class, 'reserve'])->middleware('throttle:10,1');
Route::get('reservations/slots', [ReservationController::class, 'showSlots']);
Route::get('reservations/by-date', [ReservationController::class, 'byDate']);
Route::get('slot-count', [ReservationController::class, 'slotCount']);
Route::get('system-config/available-parking-slots', [SystemConfigController::class, 'getAvailableParkingSlots']);
Route::get('slots/{slot_id}/availability', [TimeSlotController::class, 'availability']);
Route::post('/temp-reservation', [TempReservationController::class, 'store']);
Route::post('reservations/from-temp', [ReservationController::class, 'storeFromTemp']);
Route::post('reservations/send-free-confirmation', [ReservationController::class, 'sendFreeConfirmation']);

// Payment callback - izuzet iz CSRF jer banka ne može da šalje token
Route::match(['GET', 'POST'], 'payment/callback', [PaymentController::class, 'callback'])->name('api.payment.callback');

Route::any('/debug-csrf', function (Request $request) {
    return response()->json([
        'method' => $request->method(),
        'path' => $request->path(),
        'all' => $request->all(),
        'user' => auth()->user(),
        'session' => session()->all(),
    ]);
});

// ====== BANKART HPP API endpoint: KORISTI API VARIJANTU, NE redirectToHpp ======
// Route::post('reservations/reserve-and-pay', [PaymentController::class, 'reserveAndPayApi']); // <-- OBRIŠI ILI KOMENTARIŠI

// Rute za slanje email-ova
Route::post('send-payment-confirmation', [MailController::class, 'sendPaymentConfirmation'])->name('api.mail.payment-confirmation');
Route::post('send-reservation-confirmation', [MailController::class, 'sendReservationConfirmation'])->name('api.mail.reservation-confirmation');

// Test rute (po potrebi ukloni iz produkcije)
//Route::get('test', fn() => response()->json(['ok' => true]));
//Route::get('cors-test', fn() => response()->json(['ok' => true]));

// ===== ADMIN I AUTENTIFIKOVANI KORISNICI =====

// Autentifikacija admina (login, logout)
Route::post('admin/login', [AdminController::class, 'login']);
Route::post('admin/logout', [AdminController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/readonly-admin/login', [ReadonlyAdminController::class, 'login']);

// SVI PRIJAVLJENI KORISNICI (autentifikovani)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('reservations', [ReservationController::class, 'index']);
    Route::get('reservations/{reservation}', [ReservationController::class, 'show']);
    // ...dodaj još rute za sve prijavljene korisnike ovdje ako treba
});

// SAMO ADMIN
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // Blokiranje slotova i dana
    Route::post('admin/block_slots', [AdminController::class, 'blockSlots']);
    Route::post('admin/block_day', [AdminController::class, 'blockDay']);
    Route::post('admin/deblock_slots', [AdminController::class, 'deblockSlots']);
    Route::post('admin/deblock_day', [AdminController::class, 'deblockDay']);
    Route::post('admin/update_slots', [AdminController::class, 'updateSlots']);
    Route::get('blocked-slots/{date}', [AdminController::class, 'getBlockedSlots'])->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');
    Route::get('blocked-days', [AdminController::class, 'getAllBlockedDays']);
Route::get('unavailable-days', [AdminController::class, 'getUnavailableDays']);

Route::post('delete-failed-payments', [AdminController::class, 'deleteFailedPayments']);
Route::post('check-successful-payments', [AdminController::class, 'checkSuccessfulPayments']);

    Route::get('reservations-for-date/{date}', [AdminController::class, 'getReservationsForDate'])->where('date', '[0-9]+');
    Route::get('admin/export-reservations/{date}', [AdminController::class, 'exportReservationsToTxt'])->where('date', '[0-9]+');
    
    // Nove rute za proveru rezervacija i blokiranje samo slobodnih termina
          Route::post('admin/check-existing-reservations', [AdminController::class, 'checkExistingReservations']);
      Route::post('admin/block-only-available-slots', [AdminController::class, 'blockOnlyAvailableSlots']);
      Route::post('admin/generate-reservations-txt', [AdminController::class, 'generateReservationsTxt']);
      Route::post('admin/log-reservations', [AdminController::class, 'logReservations']);
    Route::get('test-admin', function() { return response()->json(['status' => 'admin route works']); });

    // Upravljanje slotovima (sem index i show)
    Route::apiResource('timeslots', TimeSlotController::class)->except(['index', 'show']);

    // Upravljanje vrstama vozila (sem index i show)
    Route::apiResource('vehicle-types', VehicleTypeController::class)->except(['index', 'show']);
    Route::get('admin/vehicle-types', [VehicleTypeController::class, 'index']);
    

    // Upravljanje admin korisnicima (sem index)
    Route::apiResource('admins', AdminController::class)->except(['index']);
    Route::get('admins', [AdminController::class, 'index']); // svi admini

    // Upravljanje rezervacijama
    Route::delete('reservations/{reservation}', [ReservationController::class, 'destroy']);
    Route::patch('reservations/{id}/status', [ReservationController::class, 'updateStatus']);
    Route::put('admin/reservation/{id}', [ReservationController::class, 'update']);
    Route::post('admin/reservation_free/{reservation}', [AdminController::class, 'freeReservation']);
    Route::get('admin/reservation/{reservation}', [AdminController::class, 'showReservation']);
    Route::post('/reservation/{id}/storno', [ReservationController::class, 'stornoFiskalniRacun']);
    Route::get('/admin/search-reservations', function (\Illuminate\Http\Request $request) {
        $merchant_transaction_id = $request->query('merchant_transaction_id');
        $date = $request->query('date');
        $user_name = $request->query('user_name');
        $email = $request->query('email');
        $vehicle_type_id = $request->query('vehicle_type_id');
        $license_plate = $request->query('license_plate');
        $status = $request->query('status');
        
        $query = \App\Models\Reservation::query();
        
        if ($merchant_transaction_id) $query->where('merchant_transaction_id', 'like', '%' . $merchant_transaction_id . '%');
        if ($date) $query->where('reservation_date', $date);
        if ($user_name) $query->where('user_name', 'like', '%' . $user_name . '%');
        if ($email) $query->where('email', 'like', '%' . $email . '%');
        if ($vehicle_type_id) $query->where('vehicle_type_id', $vehicle_type_id);
        if ($license_plate) $query->where('license_plate', 'like', '%' . $license_plate . '%');
        if ($status) $query->where('status', $status);
        
        return $query->orderBy('created_at', 'desc')->get();
    });

    // Sistem konfiguracija
    Route::post('system-config', [SystemConfigController::class, 'store']);

    // Analytics endpoint
    Route::get('admin/analytics', [AdminController::class, 'analytics']);

    // Zaključak: Ne koristi više AdminController::updateReservation!
    // Koristi ReservationController@update i rutu /api/reservation/{id} sa PUT metodom.
    // U JS koristi ID rezervacije, ne email.
    Route::put('admin/reservation/{id}', [ReservationController::class, 'update']);
    Route::get('reservation/{id}', [ReservationController::class, 'show']);
});

// Sanctum CSRF route
Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show']);

// Test route in API group (no CSRF)
Route::post('/test-api', function () {
    return response()->json(['status' => 'API POST works', 'time' => now()]);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Report emails management
Route::get('/report-emails', function () {
    return \DB::table('report_emails')->pluck('email');
});
Route::post('/report-emails', function (\Illuminate\Http\Request $request) {
    $email = $request->input('email');
    if (!$email) return response()->json(['error' => 'Email required'], 422);
    \DB::table('report_emails')->insert(['email' => $email]);
    return response()->json(['success' => true]);
});
Route::delete('/report-emails/{email}', function ($email) {
    \DB::table('report_emails')->where('email', $email)->delete();
    return response()->json(['success' => true]);
});

Route::get('/num-slots', function () {
    $num = SystemConfig::where('name', 'available_parking_slots')->value('value');
    return response()->json(['num_slots' => (int)$num]);
});

Route::post('/num-slots', function (\Illuminate\Http\Request $request) {
    $validated = $request->validate([
        'num_slots' => 'required|integer|min:1|max:100'
    ]);
    $config = SystemConfig::where('name', 'available_parking_slots')->first();
    if ($config) {
        $config->value = $validated['num_slots'];
        $config->save();
    } else {
        SystemConfig::create([
            'name' => 'available_parking_slots',
            'value' => $validated['num_slots']
        ]);
    }
    return response()->json(['success' => true]);
});



Route::put('reservation/{id}', [\App\Http\Controllers\ReservationController::class, 'update']);

// Test rezervacije generator
Route::post('/generate-test-reservations', [\App\Http\Controllers\TestReservationController::class, 'generate']);

// Admin API rute
Route::prefix('admin')->group(function () {
    Route::post('login', [AdminController::class, 'login']);
    
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('reservations', [AdminController::class, 'index']);
        Route::get('reservations/{id}', [AdminController::class, 'show']);
        Route::post('reservations', [AdminController::class, 'store']);
        Route::put('reservations/{id}', [AdminController::class, 'update']);
        Route::delete('reservations/{id}', [AdminController::class, 'destroy']);
        Route::post('logout', [AdminController::class, 'logout']);
        
        // Ostale admin funkcionalnosti
        Route::get('vehicle-types', [VehicleTypeController::class, 'index']);
        Route::post('block_slots', [AdminController::class, 'blockSlots']);
        Route::post('block_day', [AdminController::class, 'blockDay']);
        Route::get('reservation/{email}', [AdminController::class, 'showReservation']);
        
        // Nove rute za promenu datuma i termina rezervacije
        Route::post('check-slot-availability', [AdminController::class, 'checkSlotAvailability']);
        Route::post('change-reservation-datetime', [AdminController::class, 'changeReservationDateTime']);
        

    });
});