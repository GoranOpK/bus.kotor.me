<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AdminReadonlyController extends Controller
{
    public function todaysReservedSlots()
    {
        // Možeš podatke povući direktno iz modela ili (pojednostavljeno) iz API-ja
        // Ako koristiš API:
        // $response = Http::get(route('api.timeslots.reserved-today'));
        // $intervals = $response->json('data');

        // Ako koristiš model/servis direktno:
        $date = now()->format('Y-m-d');
        $intervals = app(\App\Http\Controllers\TimeSlotController::class)->reservedSlotsToday()->getData(true)['data'] ?? [];

        $server_time = now()->format('Y-m-d H:i:s');
        return view('admin.auth.todays_reserved_slots', compact('intervals', 'server_time'));
    }
}