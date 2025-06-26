<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Reservation;
use DB;

class ControlController extends Controller
{
    /**
     * Vraća JSON za 3 vremenska intervala za danas, po slotovima, za readonly admina.
     * Prikazuje samo rezervacije sa statusom 'paid'.
     * Svaki slot predstavlja vremenski interval iz tabele list_of_time_slots.
     */
    public function todaysReservations()
    {
        // Lista svih slotova za dan (id, time_range)
        $allSlots = DB::table('list_of_time_slots')->orderBy('id')->get();

        // Pronađi slotove za danas na osnovu vremena
        $now = Carbon::now();
        $today = $now->toDateString();

        // Nađi trenutni slot (prvi čiji je kraj veći od sada)
        $currentSlotIndex = null;
        foreach ($allSlots as $idx => $slot) {
            // Parsiraj kraj intervala
            [$slotStart, $slotEnd] = explode(' - ', $slot->time_range);

            // Za kraj koristi današnji datum
            $slotEndCarbon = Carbon::createFromFormat('H:i', trim($slotEnd), $now->timezone)
                ->setDateFrom($now); // koristi datum danas

            // Ako interval prelazi ponoć (npr. 20:00 - 24:00), prilagodi slotEnd na sutrašnji datum
            if ($slotEndCarbon->lessThanOrEqualTo(Carbon::createFromFormat('H:i', trim($slotStart))->setDateFrom($now))) {
                $slotEndCarbon->addDay();
            }

            if ($slotEndCarbon->gt($now)) {
                $currentSlotIndex = $idx;
                break;
            }
        }

        // Ako je van radnog vremena (nema slotova u budućnosti), vrati prazno
        if ($currentSlotIndex === null) {
            return response()->json([
                'intervals' => [],
                'server_time' => $now->format('H:i:s'),
            ]);
        }

        // Pripremi 3 intervala: trenutno aktivni + sledeća 2 (ili koliko ih ima)
        $showSlots = array_slice($allSlots->toArray(), $currentSlotIndex, 3);

        $intervals = [];
        foreach ($showSlots as $slot) {
            [$slotStart, $slotEnd] = explode(' - ', $slot->time_range);

            // Odredi datum početka i kraja intervala
            $intervalStart = Carbon::createFromFormat('H:i', trim($slotStart), $now->timezone)
                ->setDateFrom($now);
            $intervalEnd = Carbon::createFromFormat('H:i', trim($slotEnd), $now->timezone)
                ->setDateFrom($now);
            if ($intervalEnd->lessThanOrEqualTo($intervalStart)) {
                $intervalEnd->addDay();
            }

            // Rezervacije za ovaj slot (po drop_off_time_slot_id)
            $reservations = Reservation::where('reservation_date', $today)
                ->where('drop_off_time_slot_id', $slot->id)
                ->where('status', 'paid')
                ->with('vehicleType')
                ->get();

            // Grupisanje po slotu (po slotu je jedan interval, ali može više vozila)
            $vehicles = $reservations->map(function ($r) {
                return [
                    'vehicle_type' => $r->vehicleType ? $r->vehicleType->description_vehicle : '',
                    'license_plate' => $r->license_plate,
                ];
            });

            $intervals[] = [
                'interval' => $slot->time_range,
                'vehicles' => $vehicles,
            ];
        }

        return response()->json([
            'intervals' => $intervals,
            'server_time' => $now->format('H:i:s'),
        ]);
    }
}