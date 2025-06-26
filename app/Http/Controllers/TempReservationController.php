<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TempData;
use Illuminate\Support\Str;

class TempReservationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'drop_off_time_slot_id' => 'required|integer',
            'pick_up_time_slot_id'  => 'required|integer',
            'reservation_date'      => 'required|date',
            'user_name'             => 'required|string|max:255',
            'country'               => 'required|string|max:100',
            'license_plate'         => 'required|string|max:50',
            'vehicle_type_id'       => 'required|integer',
            'email'                 => 'required|email|max:255',
        ]);

        // GENERIŠI merchant_transaction_id
        $merchant_transaction_id = (string) Str::uuid();

        $temp = TempData::create(array_merge($validated, [
            'merchant_transaction_id' => $merchant_transaction_id
        ]));

        return response()->json(['merchant_transaction_id' => $merchant_transaction_id], 201);
    }
}