<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminController extends Controller
{
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
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $admin->createToken('admin-panel')->plainTextToken;

        return response()->json([
            'token' => $token,
            'admin' => $admin,
            'message' => 'Login successful',
        ], 200);
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
     * Dohvata sve blokirane dane sa grupisanim terminima
     */
    public function getAllBlockedDays()
    {
        \Log::info('getAllBlockedDays called');
        try {
            $blockedDays = [];
            
            // Dohvati sve tabele koje počinju sa godinom (2025, 2026, itd.)
            $tables = DB::select("SHOW TABLES LIKE '2025%'");
            
            foreach ($tables as $table) {
                $tableName = array_values((array)$table)[0];
                
                // Proveri da li tabela ima blokirane termine
                $blockedCount = DB::table($tableName)
                    ->where('available', 0)
                    ->count();
                
                if ($blockedCount > 0) {
                    $totalSlots = DB::table($tableName)->count();
                    $isFullyBlocked = $blockedCount === $totalSlots;
                    
                    $dayData = [
                        'date' => $tableName,
                        'is_fully_blocked' => $isFullyBlocked,
                        'blocked_slots' => []
                    ];
                    
                    if (!$isFullyBlocked) {
                        // Dohvati blokirane termine i grupuj ih
                        $blockedSlots = DB::table($tableName)
                            ->join('list_of_time_slots', $tableName . '.time_slot_id', '=', 'list_of_time_slots.id')
                            ->where($tableName . '.available', 0)
                            ->select('list_of_time_slots.time_slot', 'list_of_time_slots.id')
                            ->orderBy('list_of_time_slots.id')
                            ->get();
                        
                        $dayData['blocked_slots'] = $this->groupTimeSlots($blockedSlots);
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
                'message' => 'Termini su uspešno odblokirani.',
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
                'message' => 'Dan je uspešno odblokiran.'
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
        try {
            $date = $request->input('date');
            $slots = $request->input('slots');
            $reservations = $request->input('reservations');
            
            \Log::info('logReservations called', [
                'date' => $date,
                'slots' => $slots,
                'reservations_count' => count($reservations),
                'reservations' => $reservations
            ]);
            
            return response()->json(['success' => true]);
            
        } catch (\Throwable $e) {
            \Log::error('logReservations error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}