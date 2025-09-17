<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\TempData;
use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminController extends Controller
{
    /**
     * Dohvata maksimalni kapacitet iz system_config tabele (cache-ovano)
     */
    private function getMaxCapacity()
    {
<<<<<<< HEAD
        $defaultValue = config('app.default_available_parking_slots', 9);
=======
        $defaultValue = config('app.default_available_parking_slots', 8);
>>>>>>> edd871dd4444f817be418d934462960767b66424
        return cache()->remember('available_parking_slots', 300, function () use ($defaultValue) {
            return (int)SystemConfig::where('name', 'available_parking_slots')->value('value') ?: $defaultValue;
        });
    }

    public function index()
    {
        $admins = Admin::all();
        return response()->json($admins, 200);
    }

    public function show($id)
    {
        $admin = Admin::findOrFail($id);
        return response()->json($admin, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|email|unique:admins|max:255',
            'password' => 'required|string|min:6',
        ]);

        $admin = Admin::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);
        return response()->json($admin, 201);
    }

    public function update(Request $request, $id)
    {
        $admin = Admin::findOrFail($id);

        $validated = $request->validate([
            'username' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:admins,email,' . $id,
            'password' => 'sometimes|required|string|min:6',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $admin->update($validated);
        return response()->json($admin, 200);
    }

    public function destroy($id)
    {
        $admin = Admin::findOrFail($id);
        $admin->delete();
        return response()->json(['message' => 'Admin deleted successfully'], 200);
    }

    public function login(Request $request)
    {
        \Log::info('Admin login attempt', [
            'method' => $request->method(),
            'url' => $request->url(),
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'content_type' => $request->header('Content-Type')
        ]);

        try {
            $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ]);

            // Prihvata i 'username' i 'email' polje
            $email = $request->input('username') ?: $request->input('email');
            
            \Log::info('Email extracted', ['email' => $email]);
            
            if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                \Log::warning('Invalid email format', ['email' => $email]);
                return response()->json(['message' => 'Invalid email format'], 400);
            }

            $admin = Admin::where('email', $email)->first();

            if (!$admin) {
                \Log::warning('Admin not found', ['email' => $email]);
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            if (!Hash::check($request->password, $admin->password)) {
                \Log::warning('Invalid password for admin', ['email' => $email]);
                return response()->json(['message' => 'Invalid credentials'], 401);
            }

            $token = $admin->createToken('admin-panel')->plainTextToken;

            \Log::info('Admin login successful', ['email' => $email, 'admin_id' => $admin->id]);

            return response()->json([
                'token' => $token,
                'admin' => $admin,
                'message' => 'Login successful',
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Admin login error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json(['message' => 'Server error occurred'], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    public function testDnevniFinansijski()
    {
        return response()->json(['status' => 'ok', 'message' => 'Test dnevni finansijski izvještaj']);
    }

    /**
     * Blokira slotove za određeni dan (dinamička tabela) -- PREMA ID-jevima slotova
     */
    public function blockSlots(Request $request)
    {
        \Log::info('blockSlots start', ['request' => $request->all()]);
        try {
            $data = $request->validate([
                'date' => 'required|date',
                'slots' => 'required|array|min:1',
                'slots.*' => 'integer'
            ]);

            $table = str_replace('-', '', $data['date']);

            if (!Schema::hasTable($table)) {
                return response()->json(['success' => false, 'message' => "Tabela za dan $table ne postoji."], 404);
            }

            $affected = [];
            foreach ($data['slots'] as $slotId) {
                $updated = DB::table($table)
                    ->where('time_slot_id', $slotId)
                    ->update(['available' => 0]);

                if ($updated) {
                    $affected[] = $slotId;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Slotovi su blokirani.',
                'blocked_slots' => $affected,
            ]);
        } catch (\Throwable $e) {
            \Log::error('blockSlots error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'input' => $request->all()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function blockDay(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date'
        ]);

        // Formatiraj datum u ime tabele (npr. 2025-07-30 -> 20250730)
        $table = date('Ymd', strtotime($validated['date']));

        \Log::info('blockDay called', ['date' => $validated['date'], 'table' => $table]);

        // Proveri da li tabela postoji
        if (!\Schema::hasTable($table)) {
            \Log::error('Table does not exist for blocking day', ['table' => $table]);
            return response()->json(['error' => 'Tabela za taj dan ne postoji!'], 404);
        }

        \Log::info('Table exists, blocking all slots');

        // Postavi remaining i available na 0 za sve slotove tog dana
        $updated = \DB::table($table)->update(['remaining' => 0, 'available' => 0]);

        \Log::info('Day blocked', ['updated_rows' => $updated]);

        return response()->json(['success' => true]);
    }

    public function showReservation($email)
    {
        $reservation = \App\Models\Reservation::where('email', $email)->orderByDesc('id')->first();

        if (!$reservation) {
            return response()->json(['message' => 'Rezervacija nije pronađena!'], 404);
        }

        // Vrati samo potrebna polja
        return response()->json([
            'merchant_transaction_id' => $reservation->merchant_transaction_id,
            'user_name' => $reservation->user_name,
            'country' => $reservation->country,
            'license_plate' => $reservation->license_plate,
            'email' => $reservation->email,
            'vehicle_type_id' => $reservation->vehicle_type_id,
            'drop_off_time_slot_id' => $reservation->drop_off_time_slot_id,
            'pick_up_time_slot_id' => $reservation->pick_up_time_slot_id,
            'date' => $reservation->date,
            'id' => $reservation->id,
        ]);
    }

    /**
     * Dohvata blokirane termine za određeni datum
     */
    public function getBlockedSlots($date)
    {
        try {
            \Log::info('getBlockedSlots called', ['date' => $date]);
            
            // Konvertuj YYYY-MM-DD u YYYYMMDD format za ime tabele
            $tableName = str_replace('-', '', $date);
            
            // Proveri da li tabela postoji
            $tableExists = Schema::hasTable($tableName);
            \Log::info('Table exists check', ['table' => $tableName, 'exists' => $tableExists]);
            
            if (!$tableExists) {
                \Log::info('Table does not exist', ['table' => $tableName]);
                return response()->json([], 200);
            }

            \Log::info('Table exists, checking for blocked slots');

            // Dohvati sve termine koji su blokirani (available = 0)
            $blockedSlots = DB::table($tableName)
                ->where('available', 0)
                ->select('id', 'time_slot_id', 'remaining', 'available')
                ->get();

            \Log::info('Blocked slots found', ['count' => $blockedSlots->count(), 'slots' => $blockedSlots->toArray()]);

            return response()->json($blockedSlots);
        } catch (\Throwable $e) {
            \Log::error('getBlockedSlots error: ' . $e->getMessage(), [
                'date' => $date,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Dohvata sve blokirane dane sa grupisanim terminima (ručno blokirani)
     */
    public function getAllBlockedDays()
    {
        try {
            $blockedDays = [];
            
            // Dohvati sve tabele koje počinju sa godinom (2025, 2026, itd.)
            $tables = DB::select("SHOW TABLES LIKE '2025%'");
            
            foreach ($tables as $table) {
                // SHOW TABLES vraća objekat sa dinamičkim imenom kolone
                $tableName = array_values((array)$table)[0];
                
                // Proveri da li tabela ima ručno blokirane termine
                try {
                    // Ručno blokirani termini su oni koji imaju available = 0 ali nemaju rezervacije
                    $manuallyBlockedCount = DB::table($tableName)
                        ->where('available', 0)
                        ->whereNotExists(function ($query) use ($tableName) {
                            $query->select(DB::raw(1))
                                  ->from('reservations')
                                  ->whereRaw("reservations.reservation_date = ?", [substr($tableName, 0, 4) . '-' . substr($tableName, 4, 2) . '-' . substr($tableName, 6, 2)])
                                  ->where(function ($subQuery) use ($tableName) {
                                      $subQuery->whereRaw("reservations.drop_off_time_slot_id = `$tableName`.time_slot_id")
                                              ->orWhereRaw("reservations.pick_up_time_slot_id = `$tableName`.time_slot_id");
                                  });
                        })
                        ->count();
                    
                    $totalSlots = DB::table($tableName)->count();
                } catch (\Exception $e) {
                    \Log::error("Error querying table $tableName: " . $e->getMessage());
                    continue; // Preskoči ovu tabelu ako ima grešku
                }
                
                if ($manuallyBlockedCount > 0) {
                    $totalSlots = DB::table($tableName)->count();
                    $isFullyBlocked = $manuallyBlockedCount === $totalSlots;
                    
                    $dayData = [
                        'date' => $tableName,
                        'is_fully_blocked' => $isFullyBlocked,
                        'blocked_slots' => []
                    ];
                                    
                    if (!$isFullyBlocked) {
                        try {
                            // Dohvati ručno blokirane termine i grupuj ih
                            $blockedSlots = DB::table($tableName)
                                ->join('list_of_time_slots', $tableName . '.time_slot_id', '=', 'list_of_time_slots.id')
                                ->where($tableName . '.available', 0)
                                ->whereNotExists(function ($query) use ($tableName) {
                                    $query->select(DB::raw(1))
                                          ->from('reservations')
                                          ->whereRaw("reservations.reservation_date = ?", [substr($tableName, 0, 4) . '-' . substr($tableName, 4, 2) . '-' . substr($tableName, 6, 2)])
                                          ->where(function ($subQuery) use ($tableName) {
                                              $subQuery->whereRaw("reservations.drop_off_time_slot_id = `$tableName`.time_slot_id")
                                                      ->orWhereRaw("reservations.pick_up_time_slot_id = `$tableName`.time_slot_id");
                                          });
                                })
                                ->select('list_of_time_slots.time_slot', 'list_of_time_slots.id')
                                ->orderBy('list_of_time_slots.id')
                                ->get();
                            
                            $dayData['blocked_slots'] = $this->groupTimeSlots($blockedSlots);
                        } catch (\Exception $e) {
                            \Log::error("Error joining table $tableName with list_of_time_slots: " . $e->getMessage());
                            $dayData['blocked_slots'] = [];
                        }
                    }
                    $blockedDays[] = $dayData;
                }
            }
            
            // Sortiraj po datumu (najbliži prvi - rastući redosled)
            usort($blockedDays, function($a, $b) {
                return strcmp($a['date'], $b['date']);
            });
            
            return response()->json($blockedDays);
        } catch (\Throwable $e) {
            \Log::error('getAllBlockedDays error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Dohvata sve nedostupne dane (popunjeni rezervacijama)
     */
    public function getUnavailableDays()
    {
        try {
            $unavailableDays = [];
            
            // Dohvati sve tabele koje počinju sa godinom (2025, 2026, itd.)
            $tables = DB::select("SHOW TABLES LIKE '2025%'");
            
            foreach ($tables as $table) {
                // SHOW TABLES vraća objekat sa dinamičkim imenom kolone
                $tableName = array_values((array)$table)[0];
                
                // Proveri da li tabela ima nedostupne termine
                try {
                    // Nedostupni termini su oni koji imaju available = 0
                    $unavailableCount = DB::table($tableName)
                        ->where('available', 0)
                        ->count();
                    
                    $totalSlots = DB::table($tableName)->count();
                } catch (\Exception $e) {
                    \Log::error("Error querying table $tableName: " . $e->getMessage());
                    continue; // Preskoči ovu tabelu ako ima grešku
                }
                
                if ($unavailableCount > 0) {
                    $isFullyUnavailable = $unavailableCount === $totalSlots;
                    
                    $dayData = [
                        'date' => $tableName,
                        'is_fully_unavailable' => $isFullyUnavailable,
                        'unavailable_slots' => []
                    ];
                    
                    if (!$isFullyUnavailable) {
                        try {
                            // Dohvati sve nedostupne termine (available = 0)
                            $unavailableSlots = DB::table($tableName)
                                ->join('list_of_time_slots', $tableName . '.time_slot_id', '=', 'list_of_time_slots.id')
                                ->where($tableName . '.available', 0)
                                ->select('list_of_time_slots.time_slot', 'list_of_time_slots.id')
                                ->orderBy('list_of_time_slots.id')
                                ->get();
                            
                            $dayData['unavailable_slots'] = $this->groupTimeSlots($unavailableSlots);
                        } catch (\Exception $e) {
                            \Log::error("Error joining table $tableName with list_of_time_slots: " . $e->getMessage());
                            $dayData['unavailable_slots'] = [];
                        }
                    }
                    $unavailableDays[] = $dayData;
                }
            }
            
            // Sortiraj po datumu (najbliži prvi - rastući redosled)
            usort($unavailableDays, function($a, $b) {
                return strcmp($a['date'], $b['date']);
            });
            
            return response()->json($unavailableDays);
        } catch (\Throwable $e) {
            \Log::error('getUnavailableDays error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Grupuje povezane termine u jedan opseg
     */
    private function groupTimeSlots($slots)
    {
        if ($slots->isEmpty()) {
            return [];
        }
        
        $grouped = [];
        $currentGroup = [];
        
        foreach ($slots as $slot) {
            if (empty($currentGroup)) {
                $currentGroup[] = $slot;
            } else {
                $lastSlot = end($currentGroup);
                $lastId = $lastSlot->id;
                $currentId = $slot->id;
                
                // Ako su termini povezani (razlika je 1)
                if ($currentId - $lastId === 1) {
                    $currentGroup[] = $slot;
                } else {
                    // Završi trenutnu grupu i počni novu
                    $grouped[] = $this->createTimeRange($currentGroup);
                    $currentGroup = [$slot];
                }
            }
        }
        
        // Dodaj poslednju grupu
        if (!empty($currentGroup)) {
            $grouped[] = $this->createTimeRange($currentGroup);
        }
        
        return $grouped;
    }
    
    /**
     * Kreira opseg vremena iz grupe termina
     */
    private function createTimeRange($group)
    {
        if (count($group) === 1) {
            return ['time_range' => $group[0]->time_slot];
        }
        
        $firstSlot = $group[0]->time_slot;
        $lastSlot = end($group)->time_slot;
        
        // Izvuci početno i krajnje vreme
        $startTime = explode(' - ', $firstSlot)[0];
        $endTime = explode(' - ', $lastSlot)[1];
        
        return ['time_range' => $startTime . ' - ' . $endTime];
    }

    /**
     * Odblokira određene termine za datum
     */
    public function deblockSlots(Request $request)
    {
        try {
            $validated = $request->validate([
                'date' => 'required|date',
                'slots' => 'required|array|min:1',
                'slots.*' => 'integer'
            ]);

            $date = $validated['date'];
            $slots = $validated['slots'];
            
            // Sortiraj slotove
            sort($slots);
            $firstSlot = $slots[0];
            $lastSlot = end($slots);

            // Pozovi MySQL proceduru
            DB::select('CALL DeblockSlotsForDate(?, ?, ?)', [$date, $firstSlot, $lastSlot]);

            return response()->json([
                'success' => true,
                'message' => 'Termini su uspješno odblokirani.',
                'deblocked_slots' => $slots
            ]);
        } catch (\Throwable $e) {
            \Log::error('deblockSlots error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'input' => $request->all()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Odblokira ceo dan
     */
    public function deblockDay(Request $request)
    {
        try {
            $validated = $request->validate([
                'date' => 'required|date'
            ]);

            $date = $validated['date'];

            // Pozovi MySQL proceduru
            DB::select('CALL DeblockTableForDate(?)', [$date]);

            return response()->json([
                'success' => true,
                'message' => 'Dan je uspješno odblokiran.'
            ]);
        } catch (\Throwable $e) {
            \Log::error('deblockDay error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'input' => $request->all()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Proverava postojeće rezervacije za dati datum
     */
    public function checkExistingReservations(Request $request)
    {
        try {
            $validated = $request->validate([
                'date' => 'required|date'
            ]);

            $date = $validated['date'];

            // Pozovi MySQL proceduru za proveru rezervacija
            $reservations = DB::select('CALL CheckExistingReservations(?)', [$date]);

            return response()->json([
                'success' => true,
                'reservations' => $reservations,
                'count' => count($reservations)
            ]);
        } catch (\Throwable $e) {
            \Log::error('checkExistingReservations error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'input' => $request->all()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Blokira samo slobodne termine (bez postojećih rezervacija)
     */
    public function blockOnlyAvailableSlots(Request $request)
    {
        try {
            $validated = $request->validate([
                'date' => 'required|date'
            ]);

            $date = $validated['date'];

            // Pozovi MySQL proceduru za blokiranje samo slobodnih termina
            DB::select('CALL BlockOnlyAvailableSlots(?)', [$date]);

            return response()->json([
                'success' => true,
                'message' => 'Samo slobodni termini su blokirani.'
            ]);
        } catch (\Throwable $e) {
            \Log::error('blockOnlyAvailableSlots error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'input' => $request->all()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generiše TXT fajl sa podacima rezervacija za dati datum
     */
    public function generateReservationsTxt(Request $request)
    {
        try {
            $validated = $request->validate([
                'date' => 'required|date'
            ]);

            $date = $validated['date'];

            // Dohvati rezervacije
            $reservations = DB::select('CALL CheckExistingReservations(?)', [$date]);

            if (empty($reservations)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nema rezervacija za ovaj datum.'
                ]);
            }

            // Generiši TXT sadržaj
            $content = "REZERVACIJE ZA DATUM: " . $date . "\n";
            $content .= "=====================================\n\n";

            foreach ($reservations as $index => $reservation) {
                $content .= "REZERVACIJA #" . ($index + 1) . "\n";
                $content .= "ID: " . ($reservation->id ?? 'N/A') . "\n";
                $content .= "Ime i prezime: " . ($reservation->user_name ?? 'N/A') . "\n";
                $content .= "Email: " . ($reservation->email ?? 'N/A') . "\n";
                $content .= "Država: " . ($reservation->country ?? 'N/A') . "\n";
                $content .= "Registarska oznaka: " . ($reservation->license_plate ?? 'N/A') . "\n";
                $content .= "Tip vozila: " . ($reservation->vehicle_type ?? 'N/A') . "\n";
                $content .= "Status: " . ($reservation->status ?? 'N/A') . "\n";
                $content .= "Merchant ID: " . ($reservation->merchant_transaction_id ?? 'N/A') . "\n";
                $content .= "Drop-off termin: " . ($reservation->drop_off_time ?? 'N/A') . "\n";
                $content .= "Pick-up termin: " . ($reservation->pick_up_time ?? 'N/A') . "\n";
                $content .= "-------------------------------------\n\n";
            }

            $content .= "UKUPNO REZERVACIJA: " . count($reservations) . "\n";
            $content .= "DATUM GENERISANJA: " . now()->format('Y-m-d H:i:s') . "\n";

            // Vrati TXT sadržaj kao response
            return response($content)
                ->header('Content-Type', 'text/plain')
                ->header('Content-Disposition', 'attachment; filename="rezervacije_' . $date . '.txt"');

        } catch (\Throwable $e) {
            \Log::error('generateReservationsTxt error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'input' => $request->all()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function logReservations(Request $request)
    {
        $date = $request->input('date');
        if (!$date) {
            return response()->json(['error' => 'Date is required'], 400);
        }

        // Kreiraj tabelu za datum ako ne postoji
        $tableName = date('Ymd', strtotime($date));
        if (!Schema::hasTable($tableName)) {
            return response()->json(['error' => 'Table for date does not exist'], 404);
        }

        // Dohvati sve rezervacije za taj datum
        $reservations = DB::table('reservations')
            ->where('reservation_date', $date)
            ->orderBy('created_at')
            ->get();

        $logContent = "=== REZERVACIJE ZA DATUM: {$date} ===\n";
        $logContent .= "Ukupno rezervacija: " . $reservations->count() . "\n\n";

        foreach ($reservations as $reservation) {
            $logContent .= "ID: {$reservation->id}\n";
            $logContent .= "Ime: {$reservation->user_name}\n";
            $logContent .= "Email: {$reservation->email}\n";
            $logContent .= "Registarska oznaka: {$reservation->license_plate}\n";
            $logContent .= "Status: {$reservation->status}\n";
            $logContent .= "Datum kreiranja: {$reservation->created_at}\n";
            $logContent .= "---\n";
        }

        // Sačuvaj u fajl
        $filename = "reservations_log_{$date}.txt";
        $filepath = storage_path("logs/{$filename}");
        file_put_contents($filepath, $logContent);

        return response()->json([
            'success' => true,
            'message' => 'Reservations logged successfully',
            'filename' => $filename,
            'count' => $reservations->count()
        ]);
    }

    /**
     * Analytics endpoint za analizu rezervacija
     */
    public function analytics(Request $request)
    {
        \Log::info('Analytics endpoint called', [
            'type' => $request->input('type'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'include_free' => $request->input('include_free'),
            'method' => $request->method(),
            'all_inputs' => $request->all(),
            'user' => auth()->user(),
            'token' => $request->bearerToken()
        ]);

        $type = $request->input('type');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $includeFree = $request->input('include_free', '0') === '1';

        // Validacija parametara
        if (!$type || !$startDate || !$endDate) {
            return response()->json(['error' => 'Missing required parameters: type, start_date, end_date'], 400);
        }

        // Validacija tipa analitike
        $validTypes = ['time_slots', 'vehicle_types', 'countries'];
        if (!in_array($type, $validTypes)) {
            return response()->json(['error' => 'Invalid type. Must be one of: ' . implode(', ', $validTypes)], 400);
        }

        try {
            \Log::info('Creating base query', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'include_free' => $includeFree,
                'include_free_type' => gettype($includeFree)
            ]);
            
            $query = DB::table('reservations')
                ->whereBetween('reservation_date', [$startDate, $endDate]);

            // Filtriraj besplatne rezervacije ako je potrebno
            if (!$includeFree) {
                $query->where('status', 'paid');
                \Log::info('Added paid filter');
            } else {
                \Log::info('Including free reservations');
            }

            \Log::info('Analytics query prepared', [
                'type' => $type,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'include_free' => $includeFree
            ]);

            switch ($type) {
                case 'time_slots':
                    \Log::info('Calling getTimeSlotAnalytics');
                    return $this->getTimeSlotAnalytics($query, $startDate, $endDate, $includeFree);
                case 'vehicle_types':
                    return $this->getVehicleTypeAnalytics($query);
                case 'countries':
                    return $this->getCountryAnalytics($query);
                default:
                    return response()->json(['error' => 'Invalid analytics type'], 400);
            }
        } catch (\Exception $e) {
            \Log::error('Analytics error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Internal server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Analiza po vremenskim slotovima - ukupna zauzetost termina
     */
    private function getTimeSlotAnalytics($baseQuery, $startDate, $endDate, $includeFree)
    {
        try {
            \Log::info('getTimeSlotAnalytics called', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'include_free' => $includeFree,
                'include_free_type' => gettype($includeFree)
            ]);
            
            // Dohvati sve slotove iz baze
            $allSlots = DB::table('list_of_time_slots')
                ->select('id', 'time_slot')
                ->orderBy('time_slot')
                ->get();

        $result = [];

        // Dohvati sve rezervacije u periodu jednom (koristi prosleđeni filter)
        \Log::info('Getting reservations with filter', [
            'include_free' => $includeFree,
            'base_query_sql' => (clone $baseQuery)->toSql(),
            'base_query_bindings' => (clone $baseQuery)->getBindings()
        ]);
        
        try {
            $allReservations = (clone $baseQuery)
                ->select('id', 'drop_off_time_slot_id', 'pick_up_time_slot_id')
                ->get();
                
            \Log::info('Reservations query executed successfully', [
                'reservations_count' => $allReservations->count()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error executing reservations query: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }

        \Log::info('All reservations in period', [
            'total_reservations' => $allReservations->count(),
            'sample_reservations' => $allReservations->take(3)->toArray(),
            'all_slots_count' => $allSlots->count(),
            'first_slot' => $allSlots->first(),
            'last_slot' => $allSlots->last()
        ]);

        // Izračunaj prosečnu popunjenost za svaki termin u periodu
        $totalUniqueReservations = $allReservations->count();
        $totalDays = (strtotime($endDate) - strtotime($startDate)) / (60 * 60 * 24) + 1; // Broj dana u periodu
        
        // Prebroj pick-up i drop-off događaje po vremenskim periodima
        $slotAverages = [];
        foreach ($allSlots as $slot) {
            // Broj pick-up događaja u ovom slotu
            $pickupCount = $allReservations->filter(function($reservation) use ($slot) {
                return $reservation->pick_up_time_slot_id == $slot->id;
            })->count();
            
            // Broj drop-off događaja u ovom slotu
            $dropoffCount = $allReservations->filter(function($reservation) use ($slot) {
                return $reservation->drop_off_time_slot_id == $slot->id;
            })->count();
            
            // Ukupan broj događaja (pick-up + drop-off)
            $totalEvents = $pickupCount + $dropoffCount;
            
            if ($totalEvents > 0) {
                // Prosečan broj događaja po danu (celobrojno)
                $averageEventsPerDay = round($totalEvents / $totalDays);
                
                // Prosečna popunjenost (ograničena na maksimalni kapacitet)
                $maxCapacity = $this->getMaxCapacity();
                $averageOccupancy = min($averageEventsPerDay, (int)$maxCapacity);
                
                $slotAverages[] = [
                    'time_slot' => $slot->time_slot,
                    'average_occupancy' => $averageOccupancy,
                    'total_events' => $totalEvents,
                    'pickup_count' => $pickupCount,
                    'dropoff_count' => $dropoffCount,
                    'average_events_per_day' => $averageEventsPerDay
                ];
                
                $result[] = [
                    'time_slot' => $slot->time_slot,
                    'count' => $averageOccupancy
                ];
                
                // Debug za prva 3 slota
                if (count($result) <= 3) {
                    \Log::info("Slot {$slot->time_slot} (ID: {$slot->id})", [
                        'pickup_count' => $pickupCount,
                        'dropoff_count' => $dropoffCount,
                        'total_events' => $totalEvents,
                        'average_events_per_day' => $averageEventsPerDay,
                        'average_occupancy_capped' => $averageOccupancy,
                        'total_days' => $totalDays
                    ]);
                }
            }
        }
        
        // Pronađi slot sa najvećom prosečnom popunjenošću
        $maxOccupancySlot = null;
        if (count($slotAverages) > 0) {
            $maxAverage = max(array_column($slotAverages, 'average_occupancy'));
            $maxOccupancySlot = $slotAverages[array_search($maxAverage, array_column($slotAverages, 'average_occupancy'))];
        }
        
        // Izračunaj ukupnu prosečnu popunjenost
        $totalEvents = array_sum(array_column($slotAverages, 'total_events'));
        $totalSlotsWithEvents = count($slotAverages);
        $averageOccupancy = $totalSlotsWithEvents > 0 ? round($totalEvents / ($totalSlotsWithEvents * $totalDays), 1) : 0;

        // Pronađi sve intervale koji su dostigli maksimum
        $maxCapacity = $this->getMaxCapacity();
        $maxOccupancyIntervals = [];
        foreach ($slotAverages as $slot) {
            if ($slot['average_occupancy'] >= (float)$maxCapacity) {
                $maxOccupancyIntervals[] = $slot['time_slot'];
            }
        }
        
        // Debug informacije
        \Log::info('TimeSlot Analytics Debug - FINAL LOGIC', [
            'total_unique_reservations' => $totalUniqueReservations,
            'total_days' => $totalDays,
            'total_events' => $totalEvents,
            'average_occupancy' => $averageOccupancy,
            'max_occupancy_slot' => $maxOccupancySlot,
            'max_occupancy_intervals' => $maxOccupancyIntervals,
            'sample_data' => array_slice($result, 0, 3),
            'code_version' => 'FINAL_LOGIC_V7',
            'result_sample' => array_slice($result, 0, 5)
        ]);

        // Vrati podatke sa dodatnim informacijama
        return response()->json([
            'slots' => $result,
            'summary' => [
                'total_unique_reservations' => $totalUniqueReservations,
                'max_occupancy' => $maxOccupancySlot ? $maxOccupancySlot['average_occupancy'] : 0,
                'max_occupancy_slot' => $maxOccupancySlot ? $maxOccupancySlot['time_slot'] : 'N/A',
                'max_occupancy_intervals' => $maxOccupancyIntervals,
                'avg_occupancy' => $averageOccupancy
            ]
        ]);
        
    } catch (\Exception $e) {
        \Log::error('TimeSlot Analytics Error: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(['error' => 'Internal server error: ' . $e->getMessage()], 500);
    }
    }

    /**
     * Analiza po tipovima vozila
     */
    private function getVehicleTypeAnalytics($baseQuery)
    {
        $stats = (clone $baseQuery)
            ->join('vehicle_types', 'reservations.vehicle_type_id', '=', 'vehicle_types.id')
            ->select('vehicle_types.description_vehicle as vehicle_type', DB::raw('COUNT(*) as count'))
            ->groupBy('vehicle_types.id', 'vehicle_types.description_vehicle')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'vehicle_type' => $item->vehicle_type ?: 'Nepoznato',
                    'count' => $item->count
                ];
            });

        return response()->json($stats);
    }

    /**
     * Analiza po državama
     */
    private function getCountryAnalytics($baseQuery)
    {
        $stats = (clone $baseQuery)
            ->select('country', DB::raw('COUNT(*) as count'))
            ->groupBy('country')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'country' => $item->country ?: 'Nepoznato',
                    'count' => $item->count
                ];
            });

        return response()->json($stats);
    }

    /**
     * Obriši neuspešna plaćanja iz temp_data tabele na osnovu CSV fajla
     */
    public function deleteFailedPayments(Request $request)
    {
        \Log::info('=== POČETAK deleteFailedPayments ===', [
            'request_data' => $request->all()
        ]);
        
        try {
            // Validacija podataka
            $request->validate([
                'merchant_transaction_ids' => 'required|array',
                'merchant_transaction_ids.*' => 'required|string|max:64'
            ]);

            $merchantIds = $request->input('merchant_transaction_ids');
            $totalCount = count($merchantIds);
            
            \Log::info('deleteFailedPayments - obrada', [
                'total_merchant_ids' => $totalCount,
                'merchant_ids' => $merchantIds
            ]);

            // Pronađi sve temp_data zapise sa ovim merchant_transaction_ids
            $tempRecords = TempData::whereIn('merchant_transaction_id', $merchantIds)->get();
            $foundCount = $tempRecords->count();
            
            \Log::info('deleteFailedPayments - pronađeno', [
                'found_count' => $foundCount,
                'found_merchant_ids' => $tempRecords->pluck('merchant_transaction_id')->toArray()
            ]);
            
            // Specifična provera za merchant_transaction_id
            if (in_array('e7c3221d-0161-460f-a4be-50d02033acb3', $merchantIds)) {
                $specificRecord = $tempRecords->where('merchant_transaction_id', 'e7c3221d-0161-460f-a4be-50d02033acb3')->first();
                \Log::info('deleteFailedPayments - SPECIFIČNI merchant_transaction_id', [
                    'merchant_transaction_id' => 'e7c3221d-0161-460f-a4be-50d02033acb3',
                    'found_in_temp_data' => $specificRecord ? true : false,
                    'temp_data_record' => $specificRecord ? $specificRecord->toArray() : null
                ]);
            }

            // Obriši pronađene zapise
            $deletedCount = 0;
            foreach ($tempRecords as $tempRecord) {
                try {
                    $tempRecord->delete();
                    $deletedCount++;
                    \Log::info('deleteFailedPayments - obrisan zapis', [
                        'merchant_transaction_id' => $tempRecord->merchant_transaction_id,
                        'user_name' => $tempRecord->user_name,
                        'email' => $tempRecord->email
                    ]);
                } catch (\Exception $e) {
                    \Log::error('deleteFailedPayments - greška pri brisanju', [
                        'merchant_transaction_id' => $tempRecord->merchant_transaction_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $notFoundCount = $totalCount - $foundCount;
            
            \Log::info('deleteFailedPayments - završeno', [
                'total_processed' => $totalCount,
                'deleted_count' => $deletedCount,
                'not_found_count' => $notFoundCount
            ]);

            return response()->json([
                'success' => true,
                'total_processed' => $totalCount,
                'deleted_count' => $deletedCount,
                'not_found_count' => $notFoundCount,
                'message' => "Uspješno obrisano {$deletedCount} neuspješnih plaćanja iz temp_data tabele."
            ]);

        } catch (\Exception $e) {
            \Log::error('deleteFailedPayments - greška', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Greška pri obradi neuspješnih plaćanja.'
            ], 500);
        }
    }

    /**
     * Proveri uspešna plaćanja iz CSV fajla i pronađi one koje nisu u reservations tabeli
     */
    public function checkSuccessfulPayments(Request $request)
    {
        \Log::info('=== POČETAK checkSuccessfulPayments ===', [
            'request_data' => $request->all()
        ]);
        
        try {
            // Validacija podataka
            $request->validate([
                'csv_data' => 'required|array',
                'csv_data.*.merchantTxId' => 'required|string|max:64',
                'csv_data.*.dateUser' => 'required|string',
                'csv_data.*.amount' => 'required|numeric',
                'csv_data.*.customerEmail' => 'required|email',
                'csv_data.*.creditcardCardHolder' => 'required|string',
                'csv_data.*.creditcardBinCountry' => 'required|string'
            ]);

            $csvData = $request->input('csv_data');
            $totalCount = count($csvData);
            
            \Log::info('checkSuccessfulPayments - obrada', [
                'total_csv_records' => $totalCount
            ]);

            // Filtriraj refund transakcije
            $filteredData = [];
            $refundedCount = 0;
            
            foreach ($csvData as $record) {
                $merchantTxId = $record['merchantTxId'];
                
                // Proveri da li je refund transakcija
                if (strpos($merchantTxId, 'refund1-') === 0) {
                    $refundedCount++;
                    continue;
                }
                
                // Proveri da li postoji refund za ovu transakciju
                $hasRefund = false;
                foreach ($csvData as $otherRecord) {
                    if ($otherRecord['merchantTxId'] === 'refund1-' . $merchantTxId) {
                        $hasRefund = true;
                        $refundedCount++;
                        break;
                    }
                }
                
                if (!$hasRefund) {
                    $filteredData[] = $record;
                }
            }
            
            \Log::info('checkSuccessfulPayments - filtriranje', [
                'original_count' => $totalCount,
                'filtered_count' => count($filteredData),
                'refunded_count' => $refundedCount
            ]);

            // Pronađi merchant_transaction_ids koji postoje u reservations tabeli
            $merchantIds = array_column($filteredData, 'merchantTxId');
            $existingReservations = DB::table('reservations')
                ->whereIn('merchant_transaction_id', $merchantIds)
                ->pluck('merchant_transaction_id')
                ->toArray();
            
            \Log::info('checkSuccessfulPayments - postojeće rezervacije', [
                'existing_count' => count($existingReservations),
                'existing_merchant_ids' => $existingReservations
            ]);

            // Pronađi transakcije koje nisu u reservations tabeli
            $missingTransactions = [];
            foreach ($filteredData as $record) {
                $merchantTxId = $record['merchantTxId'];
                $isMissing = !in_array($merchantTxId, $existingReservations);
                
                // Dodatno logovanje za specifični merchant_transaction_id
                if ($merchantTxId === 'e7c3221d-0161-460f-a4be-50d02033acb3') {
                    \Log::info('checkSuccessfulPayments - SPECIFIČNI merchant_transaction_id', [
                        'merchantTxId' => $merchantTxId,
                        'is_missing_from_reservations' => $isMissing,
                        'exists_in_reservations' => in_array($merchantTxId, $existingReservations),
                        'record_data' => $record
                    ]);
                    
                    // Proveri da li postoji u temp_data
                    $tempDataRecord = DB::table('temp_data')->where('merchant_transaction_id', $merchantTxId)->first();
                    \Log::info('checkSuccessfulPayments - temp_data provera', [
                        'merchantTxId' => $merchantTxId,
                        'exists_in_temp_data' => $tempDataRecord ? true : false,
                        'temp_data_record' => $tempDataRecord
                    ]);
                }
                
                if ($isMissing) {
                    $missingTransactions[] = [
                        'merchantTxId' => $merchantTxId,
                        'dateUser' => $record['dateUser'],
                        'amount' => $record['amount'],
                        'customerEmail' => $record['customerEmail'],
                        'creditcardCardHolder' => $record['creditcardCardHolder'],
                        'creditcardBinCountry' => $record['creditcardBinCountry']
                    ];
                }
            }
            
            \Log::info('checkSuccessfulPayments - završeno', [
                'total_processed' => $totalCount,
                'filtered_count' => count($filteredData),
                'existing_count' => count($existingReservations),
                'missing_count' => count($missingTransactions),
                'refunded_count' => $refundedCount
            ]);

            return response()->json([
                'success' => true,
                'total_processed' => $totalCount,
                'filtered_count' => count($filteredData),
                'existing_count' => count($existingReservations),
                'missing_count' => count($missingTransactions),
                'refunded_count' => $refundedCount,
                'missing_transactions' => $missingTransactions,
                'message' => "Pronađeno " . count($missingTransactions) . " uspješnih plaćanja koja nisu u reservations tabeli."
            ]);

        } catch (\Exception $e) {
            \Log::error('checkSuccessfulPayments - greška', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Greška pri obradi uspješnih plaćanja.'
            ], 500);
        }
    }

    /**
     * Proveri dostupnost slotova za novi datum
     */
    public function checkSlotAvailability(Request $request)
    {
        try {
            $request->validate([
                'date' => 'required|date',
                'drop_off_time_slot_id' => 'required|integer|min:1|max:41',
                'pick_up_time_slot_id' => 'required|integer|min:1|max:41'
            ]);

            $date = $request->input('date');
            $dropOffSlotId = $request->input('drop_off_time_slot_id');
            $pickUpSlotId = $request->input('pick_up_time_slot_id');

            // Proveri da li je datum u budućnosti (maksimalno 90 dana)
            $today = now()->startOfDay();
            $maxDate = $today->copy()->addDays(90);
            $requestedDate = \Carbon\Carbon::parse($date)->startOfDay();

            if ($requestedDate < $today) {
                return response()->json([
                    'available' => false,
                    'error' => 'Datum ne može biti u prošlosti'
                ], 400);
            }

            if ($requestedDate > $maxDate) {
                return response()->json([
                    'available' => false,
                    'error' => 'Datum ne može biti više od 90 dana u budućnosti'
                ], 400);
            }

            // Proveri da li je pick-up termin nakon drop-off termina
            if ($pickUpSlotId <= $dropOffSlotId) {
                return response()->json([
                    'available' => false,
                    'error' => 'Pick-up termin mora biti nakon drop-off termina'
                ], 400);
            }

            // Proveri da li je današnji datum i da li su termini prošli
            $isToday = $requestedDate->isSameDay($today);
            if ($isToday) {
                $currentTime = now();
                
                // Proveri drop-off termin
                $dropOffTime = $this->getSlotTime($dropOffSlotId);
                if ($dropOffTime && $currentTime->gt($dropOffTime)) {
                    return response()->json([
                        'available' => false,
                        'error' => 'Drop-off termin je prošao'
                    ], 400);
                }

                // Proveri pick-up termin
                $pickUpTime = $this->getSlotTime($pickUpSlotId);
                if ($pickUpTime && $currentTime->gt($pickUpTime)) {
                    return response()->json([
                        'available' => false,
                        'error' => 'Pick-up termin je prošao'
                    ], 400);
                }
            }

            // Proveri dostupnost u dinamičkoj tabeli
            $tableName = $requestedDate->format('Ymd');
            
            // Proveri da li tabela postoji
            if (!Schema::hasTable($tableName)) {
                return response()->json([
                    'available' => false,
                    'error' => 'Tabela za odabrani datum ne postoji'
                ], 400);
            }

            // Proveri dostupnost drop-off slota
            $dropOffSlot = DB::table($tableName)
                ->where('time_slot_id', $dropOffSlotId)
                ->first();

            if (!$dropOffSlot || !$dropOffSlot->available || $dropOffSlot->remaining <= 0) {
                return response()->json([
                    'available' => false,
                    'error' => 'Drop-off termin nije dostupan'
                ], 400);
            }

            // Proveri dostupnost pick-up slota
            $pickUpSlot = DB::table($tableName)
                ->where('time_slot_id', $pickUpSlotId)
                ->first();

            if (!$pickUpSlot || !$pickUpSlot->available || $pickUpSlot->remaining <= 0) {
                return response()->json([
                    'available' => false,
                    'error' => 'Pick-up termin nije dostupan'
                ], 400);
            }

            // Proveri da li su termini zaključani u temp_data
            $lockedSlots = DB::table('temp_data')
                ->where('reservation_date', $date)
                ->where(function($query) use ($dropOffSlotId, $pickUpSlotId) {
                    $query->where('drop_off_time_slot_id', $dropOffSlotId)
                          ->orWhere('pick_up_time_slot_id', $pickUpSlotId);
                })
                ->where('status', 'pending')
                ->exists();

            if ($lockedSlots) {
                return response()->json([
                    'available' => false,
                    'error' => 'Termini su zaključani u temp_data'
                ], 400);
            }

            return response()->json([
                'available' => true,
                'message' => 'Termini su dostupni'
            ]);

        } catch (\Exception $e) {
            \Log::error('checkSlotAvailability - greška', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'available' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Promeni datum i termine rezervacije
     */
    public function changeReservationDateTime(Request $request)
    {
        try {
            $request->validate([
                'reservation_id' => 'required|integer|exists:reservations,id',
                'new_date' => 'required|date',
                'new_drop_off_time_slot_id' => 'required|integer|min:1|max:41',
                'new_pick_up_time_slot_id' => 'required|integer|min:1|max:41'
            ]);

            $reservationId = $request->input('reservation_id');
            $newDate = $request->input('new_date');
            $newDropOffSlotId = $request->input('new_drop_off_time_slot_id');
            $newPickUpSlotId = $request->input('new_pick_up_time_slot_id');

            // Dohvati postojeću rezervaciju
            $reservation = DB::table('reservations')->where('id', $reservationId)->first();
            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'error' => 'Rezervacija nije pronađena'
                ], 404);
            }

            $oldDate = $reservation->reservation_date;
            $oldDropOffSlotId = $reservation->drop_off_time_slot_id;
            $oldPickUpSlotId = $reservation->pick_up_time_slot_id;

            // Provera da li je rezervacija stornirana
            if ($reservation->status === 'storno') {
                return response()->json([
                    'success' => false,
                    'error' => 'Ne možete promeniti storniranu rezervaciju'
                ], 400);
            }

            // Provera da li je uopšte potrebna promena
            if ($oldDate == $newDate && $oldDropOffSlotId == $newDropOffSlotId && $oldPickUpSlotId == $newPickUpSlotId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Nema promene - isti datum i termini'
                ], 400);
            }

            // Proveri da li je promena datuma moguća
            $today = now()->startOfDay();
            $maxDate = $today->copy()->addDays(90);
            $requestedDate = \Carbon\Carbon::parse($newDate)->startOfDay();

            if ($requestedDate < $today) {
                return response()->json([
                    'success' => false,
                    'error' => 'Datum ne može biti u prošlosti'
                ], 400);
            }

            if ($requestedDate > $maxDate) {
                return response()->json([
                    'success' => false,
                    'error' => 'Datum ne može biti više od 90 dana u budućnosti'
                ], 400);
            }

            // Proveri da li je pick-up termin nakon drop-off termina
            if ($newPickUpSlotId <= $newDropOffSlotId) {
                return response()->json([
                    'success' => false,
                    'error' => 'Pick-up termin mora biti nakon drop-off termina'
                ], 400);
            }

            // Proveri da li je postojeća rezervacija besplatna (1 od 3 kombinacije)
            $isFreeReservation = $this->isFreeReservation($oldDropOffSlotId, $oldPickUpSlotId);
            $isNewFreeReservation = $this->isFreeReservation($newDropOffSlotId, $newPickUpSlotId);

            // Ako je postojeća rezervacija besplatna, nova mora biti takođe besplatna
            if ($isFreeReservation && !$isNewFreeReservation) {
                return response()->json([
                    'success' => false,
                    'error' => 'Besplatna rezervacija može biti promenjena samo u drugu besplatnu rezervaciju'
                ], 400);
            }

            // Proveri dostupnost novih termina
            $availabilityCheck = $this->checkSlotAvailabilityInternal($newDate, $newDropOffSlotId, $newPickUpSlotId);
            if (!$availabilityCheck['available']) {
                return response()->json([
                    'success' => false,
                    'error' => $availabilityCheck['error']
                ], 400);
            }

            // Izvrši promenu kroz SQL proceduru
            DB::beginTransaction();
            try {
                // Pozovi SQL proceduru
                $result = DB::select('CALL changeTimeSlot(?, ?, ?, ?, ?, ?, ?)', [
                    $reservationId,
                    $oldDate,
                    $oldDropOffSlotId,
                    $oldPickUpSlotId,
                    $newDate,
                    $newDropOffSlotId,
                    $newPickUpSlotId
                ]);

                // Ažuriraj rezervaciju
                DB::table('reservations')
                    ->where('id', $reservationId)
                    ->update([
                        'reservation_date' => $newDate,
                        'drop_off_time_slot_id' => $newDropOffSlotId,
                        'pick_up_time_slot_id' => $newPickUpSlotId,
                        'updated_at' => now()
                    ]);

                DB::commit();

                \Log::info('changeReservationDateTime - uspešno', [
                    'reservation_id' => $reservationId,
                    'old_date' => $oldDate,
                    'new_date' => $newDate,
                    'old_drop_off' => $oldDropOffSlotId,
                    'new_drop_off' => $newDropOffSlotId,
                    'old_pick_up' => $oldPickUpSlotId,
                    'new_pick_up' => $newPickUpSlotId
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Datum i termini rezervacije su uspješno promenjeni'
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('changeReservationDateTime - greška', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Proveri da li je rezervacija besplatna (1 od 3 kombinacije)
     */
    private function isFreeReservation($dropOffSlotId, $pickUpSlotId)
    {
        $freeCombinations = [
            [1, 1],   // (1, 1)
            [1, 41],  // (1, 41)
            [41, 41]  // (41, 41)
        ];

        foreach ($freeCombinations as $combination) {
            if ($dropOffSlotId == $combination[0] && $pickUpSlotId == $combination[1]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Interna provera dostupnosti slotova
     */
    private function checkSlotAvailabilityInternal($date, $dropOffSlotId, $pickUpSlotId)
    {
        try {
            $tableName = \Carbon\Carbon::parse($date)->format('Ymd');
            
            // Proveri da li tabela postoji
            if (!Schema::hasTable($tableName)) {
                return [
                    'available' => false,
                    'error' => 'Tabela za odabrani datum ne postoji'
                ];
            }

            // Proveri dostupnost drop-off slota
            $dropOffSlot = DB::table($tableName)
                ->where('time_slot_id', $dropOffSlotId)
                ->first();

            if (!$dropOffSlot || !$dropOffSlot->available || $dropOffSlot->remaining <= 0) {
                return [
                    'available' => false,
                    'error' => 'Drop-off termin nije dostupan'
                ];
            }

            // Proveri dostupnost pick-up slota
            $pickUpSlot = DB::table($tableName)
                ->where('time_slot_id', $pickUpSlotId)
                ->first();

            if (!$pickUpSlot || !$pickUpSlot->available || $pickUpSlot->remaining <= 0) {
                return [
                    'available' => false,
                    'error' => 'Pick-up termin nije dostupan'
                ];
            }

            return [
                'available' => true,
                'error' => null
            ];

        } catch (\Exception $e) {
            return [
                'available' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Dohvati vreme za slot ID
     */
    private function getSlotTime($slotId)
    {
        $slotTimes = [
            1 => '00:00', 2 => '07:00', 3 => '07:20', 4 => '07:40', 5 => '08:00',
            6 => '08:20', 7 => '08:40', 8 => '09:00', 9 => '09:20', 10 => '09:40',
            11 => '10:00', 12 => '10:20', 13 => '10:40', 14 => '11:00', 15 => '11:20',
            16 => '11:40', 17 => '12:00', 18 => '12:20', 19 => '12:40', 20 => '13:00',
            21 => '13:20', 22 => '13:40', 23 => '14:00', 24 => '14:20', 25 => '14:40',
            26 => '15:00', 27 => '15:20', 28 => '15:40', 29 => '16:00', 30 => '16:20',
            31 => '16:40', 32 => '17:00', 33 => '17:20', 34 => '17:40', 35 => '18:00',
            36 => '18:20', 37 => '18:40', 38 => '19:00', 39 => '19:20', 40 => '19:40',
            41 => '20:00'
        ];

        if (!isset($slotTimes[$slotId])) {
            return null;
        }

        $timeString = $slotTimes[$slotId];
        return \Carbon\Carbon::createFromFormat('H:i', $timeString);
    }
}