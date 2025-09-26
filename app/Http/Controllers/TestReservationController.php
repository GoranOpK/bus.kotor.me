<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestReservationController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'count' => 'required|integer|min:1|max:100'
        ]);

        $count = $request->input('count');
        $date = $request->input('date');
        $generated = 0;

        // Lista država iz script.js
        $countries = ['ME', 'HR', 'RS', 'BA', 'MK', 'SI', 'AL', 'AD', 'AT', 'BY', 'BE', 'BG', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IS', 'IE', 'IT', 'XK', 'LV', 'LI', 'LT', 'LU', 'MT', 'MD', 'MC', 'NL', 'NO', 'PL', 'PT', 'RO', 'RU', 'SM', 'SK', 'ES', 'SE', 'CH', 'UA', 'GB', 'VA', 'TR', 'IL', 'OTHER'];

        // Lista imena
        $names = [
            'Marko Petrović', 'Ana Jovanović', 'Petar Nikolić', 'Marija Đorđević', 'Stefan Stojanović',
            'Jovana Ilić', 'Nikola Marković', 'Sofija Pavlović', 'Aleksandar Milošević', 'Teodora Đurić',
            'Milan Ristić', 'Elena Popović', 'Vuk Stefanović', 'Katarina Vuković', 'Luka Radović',
            'Milica Đokić', 'Stefan Milić', 'Jana Todorović', 'Filip Stanković', 'Anđela Živković',
            'David Simić', 'Mina Kostić', 'Andrej Veličković', 'Sara Mitić', 'Lazar Božić',
            'Nikolina Vasić', 'Stefan Đorđević', 'Mila Stojković', 'Viktor Pavlović', 'Jovana Milićević'
        ];

        try {
            DB::beginTransaction();

            for ($i = 0; $i < $count; $i++) {
                // Generiši slučajne vrednosti
                $dropOffSlotId = rand(1, 20); // Prva polovina dana
                $pickUpSlotId = rand(21, 41); // Druga polovina dana
                
                // Osiguraj da je drop_off < pick_up
                if ($dropOffSlotId >= $pickUpSlotId) {
                    $pickUpSlotId = $dropOffSlotId + rand(1, 20);
                    if ($pickUpSlotId > 41) {
                        $pickUpSlotId = 41;
                    }
                }

                // Slučajan datum između 22.07.2025 i 31.07.2025
                if ($date) {
                    $reservationDate = $date;
                } else {
                    // fallback na random datum ako nije prosleđen
                    $startDate = strtotime('2025-07-21');
                    $endDate = strtotime('2025-07-21');
                    $randomTimestamp = rand($startDate, $endDate);
                    $reservationDate = date('Y-m-d', $randomTimestamp);
                }

                $userName = $names[array_rand($names)];
                $country = $countries[array_rand($countries)];
                
                // Slučajna registracija (maksimalno 7 karaktera)
                $licensePlate = strtoupper(Str::random(rand(5, 7)));
                
                $vehicleTypeId = rand(1, 4);
                
                // Slučajan email
                $email = 'test' . rand(1000, 9999) . '@example.com';
                
                // Slučajan merchant transaction ID
                $merchantTransactionId = Str::uuid()->toString();

                // Pozovi SQL proceduru
                try {
                    DB::statement('CALL AddReservation(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                        $dropOffSlotId,
                        $pickUpSlotId,
                        $reservationDate,
                        $userName,
                        $country,
                        $licensePlate,
                        $vehicleTypeId,
                        $email,
                        'paid', // status
                        $merchantTransactionId,
                        null, // fiscal_jir
                        null, // fiscal_ikof
                        null, // fiscal_qr
                        null, // fiscal_operator
                        null  // fiscal_date
                    ]);
                    $generated++;
                } catch (\Exception $e) {
                    // Slot nije dostupan, preskoči i probaj sledeći
                    continue;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'generated' => $generated,
                'message' => "Uspešno generisano {$generated} test rezervacija."
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Greška pri generisanju test rezervacija: ' . $e->getMessage()
            ], 500);
        }
    }
} 