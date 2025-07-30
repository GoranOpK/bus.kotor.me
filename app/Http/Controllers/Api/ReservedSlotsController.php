<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReservedSlotsController extends Controller
{
    public function reservedToday(Request $request)
    {
        $date = now()->toDateString();
        $now = now();

        // Učitaj sve slotove i mapiraj ih
        $slots = DB::table('list_of_time_slots')
            ->select('id', 'time_slot')
            ->orderBy('id')
            ->get()
            ->keyBy('id');

        $slotTimes = $slots->map(function($slot) use ($date) {
            [$start, $end] = explode(' - ', $slot->time_slot);
            return [
                'id' => $slot->id,
                'time_slot' => $slot->time_slot,
                'start_time' => $start,
                'end_time' => $end,
                'start_dt' => Carbon::parse($date . ' ' . $start),
                'end_dt' => Carbon::parse($date . ' ' . $end),
            ];
        });

        // Tipovi vozila
        $vehicleTypes = DB::table('vehicle_types')->pluck('description_vehicle', 'id')->toArray();

        // Sve rezervacije za danas
        $reservations = DB::table('reservations')
            ->where('reservation_date', $date)
            ->get();

        // Pronađi sve slotove koji su zauzeti kao drop-off ili pick-up i čiji kraj je u budućnosti
        $busySlots = [];
        foreach ($reservations as $res) {
            foreach (['drop_off_time_slot_id', 'pick_up_time_slot_id'] as $slotField) {
                $slotId = $res->$slotField;
                if ($slotId && isset($slotTimes[$slotId])) {
                    $slotObj = $slotTimes[$slotId];
                    // Samo slotovi čiji kraj je u budućnosti (tj. tek dolazi)
                    if ($slotObj['end_dt']->gt($now)) {
                        $busySlots[$slotId] = $slotObj;
                    }
                }
            }
        }

        // Sortiraj slotove po start_dt i uzmi naredna 3 (najbliža)
        $nextSlots = collect($busySlots)
            ->sortBy('start_dt')
            ->take(3);

        $intervals = [];
        foreach ($nextSlots as $slot) {
            // Pronađi sve rezervacije za taj slot (bilo drop-off, bilo pick-up)
            $reservationsForSlot = $reservations->filter(function($res) use ($slot) {
                return $res->drop_off_time_slot_id == $slot['id'] || $res->pick_up_time_slot_id == $slot['id'];
            })->map(function($res) use ($vehicleTypes) {
                return [
                    'vehicle_type' => $vehicleTypes[$res->vehicle_type_id] ?? 'Nepoznat tip',
                    'license_plate' => $res->license_plate,
                ];
            })->values();

            $intervals[] = [
                'interval' => $slot['time_slot'],
                'reservations' => $reservationsForSlot,
            ];
        }

        return response()->json([
            'server_time' => $now->format('Y-m-d H:i:s'),
            'data' => $intervals,
        ]);
    }
}