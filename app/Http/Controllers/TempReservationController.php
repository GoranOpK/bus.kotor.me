<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TempData;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Reservation;

class TempReservationController extends Controller
{
    /**
     * Kreiranje novih temp podataka za rezervaciju
     */
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

        // KORISTI DATABASE TRANSACTION SA SELECT FOR UPDATE I TIMEOUT-om
        return DB::transaction(function () use ($validated, $request) {
            
            // Postavi timeout za transakciju (10 sekundi)
            DB::statement('SET SESSION innodb_lock_wait_timeout = 10');
            
            try {
                // 1. PROVERI DOSTUPNOST SLOTOVA SA LOCK-om
                $slotService = app(\App\Services\SlotService::class);
                $availability = $slotService->getSlotAvailability(
                    $validated['reservation_date'], 
                    [$validated['drop_off_time_slot_id'], $validated['pick_up_time_slot_id']]
                );
                
                $dropOffRemaining = $availability[$validated['drop_off_time_slot_id']]['remaining'] ?? 0;
                $pickUpRemaining = $availability[$validated['pick_up_time_slot_id']]['remaining'] ?? 0;
                
                \Log::info('TempReservationController - provera dostupnosti slotova', [
                    'drop_off_slot' => $validated['drop_off_time_slot_id'],
                    'pick_up_slot' => $validated['pick_up_time_slot_id'],
                    'date' => $validated['reservation_date'],
                    'drop_off_remaining' => $dropOffRemaining,
                    'pick_up_remaining' => $pickUpRemaining
                ]);
                
                // 2. AKO SLOTOVI NISU DOSTUPNI - ROLLBACK
                if ($dropOffRemaining <= 0 || $pickUpRemaining <= 0) {
                    \Log::warning('TempReservationController - slotovi nisu dostupni, rollback', [
                        'drop_off_slot' => $validated['drop_off_time_slot_id'],
                        'pick_up_slot' => $validated['pick_up_time_slot_id'],
                        'drop_off_remaining' => $dropOffRemaining,
                        'pick_up_remaining' => $pickUpRemaining
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Slotovi nisu dostupni'
                    ], 400);
                }
                
                // 3. PROVERI POSTOJEĆE TEMP REZERVACIJE SAMO ZA KRITIČNE SLOTOVE (remaining = 1)
                if ($dropOffRemaining === 1 || $pickUpRemaining === 1) {
                    $existingTempReservation = TempData::where([
                        ['reservation_date', $validated['reservation_date']],
                        ['status', 'reserved'],
                        ['reserved_until', '>', now()]
                    ])->where(function($query) use ($validated) {
                        $query->where('drop_off_time_slot_id', $validated['drop_off_time_slot_id'])
                              ->orWhere('pick_up_time_slot_id', $validated['pick_up_time_slot_id']);
                    })->first();
                    
                    if ($existingTempReservation) {
                        \Log::warning('TempReservationController - kritični slotovi već rezervisani od strane drugog korisnika', [
                            'existing_merchant_transaction_id' => $existingTempReservation->merchant_transaction_id,
                            'existing_reserved_until' => $existingTempReservation->reserved_until,
                            'requested_drop_off_slot' => $validated['drop_off_time_slot_id'],
                            'requested_pick_up_slot' => $validated['pick_up_time_slot_id'],
                            'drop_off_remaining' => $dropOffRemaining,
                            'pick_up_remaining' => $pickUpRemaining,
                            'date' => $validated['reservation_date']
                        ]);
                        
                        return response()->json([
                            'success' => false,
                            'message' => 'Ovi slotovi su trenutno rezervisani od strane drugog korisnika. Molimo pokušajte za 10 minuta ili izaberite drugi termin.'
                        ], 400);
                    }
                }

                // 4. PROVERI DUPLIKATE SA LOCK-om
                $existingReservation = Reservation::where([
                    ['license_plate', $validated['license_plate']],
                    ['reservation_date', $validated['reservation_date']],
                    ['drop_off_time_slot_id', $validated['drop_off_time_slot_id']]
                ])->lockForUpdate()->first();
                
                if ($existingReservation) {
                    \Log::warning('TempReservationController - duplikat dropoff slot, rollback', [
                        'license_plate' => $validated['license_plate'],
                        'date' => $validated['reservation_date'],
                        'drop_off_slot' => $validated['drop_off_time_slot_id']
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Već postoji rezervacija za ovaj slot'
                    ], 400);
                }
                
                $existingPickup = Reservation::where([
                    ['license_plate', $validated['license_plate']],
                    ['reservation_date', $validated['reservation_date']],
                    ['pick_up_time_slot_id', $validated['pick_up_time_slot_id']]
                ])->lockForUpdate()->first();
                
                if ($existingPickup) {
                    \Log::warning('TempReservationController - duplikat pickup slot, rollback', [
                        'license_plate' => $validated['license_plate'],
                        'date' => $validated['reservation_date'],
                        'pick_up_slot' => $validated['pick_up_time_slot_id']
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'message' => 'Već postoji rezervacija za ovaj slot'
                    ], 400);
                }
                
                // 4. KREIRAJ TEMP PODATKE SA REZERVACIJOM SLOTOVA
                $merchant_transaction_id = (string) Str::uuid();
                
                // Detektuj jezik iz request-a
                $userLanguage = $request->header('Accept-Language', 'en');
                $userLanguage = (strpos($userLanguage, 'mne') !== false || strpos($userLanguage, 'sr') !== false) ? 'mne' : 'en';
                
                // KREIRAJ TEMP PODATKE SA ODGOVARAJUĆIM STATUSOM
                $tempStatus = ($dropOffRemaining === 1 || $pickUpRemaining === 1) ? 'reserved' : 'available';
                $reservedUntil = ($dropOffRemaining === 1 || $pickUpRemaining === 1) ? now()->addMinutes(10) : null;
                
                $temp = TempData::create(array_merge($validated, [
                    'merchant_transaction_id' => $merchant_transaction_id,
                    'availability_checked_at' => now(),
                    'drop_off_remaining_at_check' => $dropOffRemaining,
                    'pick_up_remaining_at_check' => $pickUpRemaining,
                    'status' => $tempStatus,  // 'reserved' samo za kritične slotove
                    'reserved_until' => $reservedUntil,  // 10 minuta samo za kritične slotove
                    'user_language' => $userLanguage
                ]));
                
                \Log::info('TempReservationController - temp podaci kreirani', [
                    'merchant_transaction_id' => $merchant_transaction_id,
                    'temp_id' => $temp->id,
                    'reservation_date' => $request->reservation_date,
                    'user_name' => $request->user_name,
                    'country' => $request->country,
                    'license_plate' => $request->license_plate,
                    'vehicle_type_id' => $request->vehicle_type_id,
                    'email' => $request->email,
                    'drop_off_time_slot_id' => $request->drop_off_time_slot_id,
                    'pick_up_time_slot_id' => $request->pick_up_time_slot_id,
                    'drop_off_remaining_at_check' => $dropOffRemaining,
                    'pick_up_remaining_at_check' => $pickUpRemaining
                ]);
                
                return response()->json([
                    'success' => true,
                    'merchant_transaction_id' => $merchant_transaction_id,
                    'message' => 'Temp podaci kreirani uspešno'
                ], 201);
                
            } catch (\Exception $e) {
                \Log::error('TempReservationController - greška u transakciji', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Automatski rollback se izvršava
                throw $e;
            }
    
        }, 5); // 5 pokušaja za retry ako dođe do deadlock-a
    }
}