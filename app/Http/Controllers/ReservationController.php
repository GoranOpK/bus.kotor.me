<?php

namespace App\Http\Controllers;

use App\Traits\HasUserMessages;
use App\Models\Reservation;
use App\Models\TempData;
use App\Models\TimeSlot;
use App\Models\SystemConfig;
use App\Services\SlotService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentReservationConfirmationMail;
use App\Mail\FreeReservationConfirmationMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\DB; // Added DB facade
use Exception; // Added Exception

class ReservationController extends Controller
{
    use HasUserMessages;
    protected $slotService;

    public function __construct(SlotService $slotService)
    {
        $this->slotService = $slotService;
    }

    /**
     * Helper function to check if a time slot allows same arrival and departure times
     * Special slots: ID 1 (00:00-7:00) and ID 41 (20:00-24:00)
     */
    private function allowsSameArrivalDeparture($slotId)
    {
        return in_array($slotId, [1, 41]);
    }

    /**
     * Rezerviši slot privremeno (10 minuta) pre plaćanja
     */
    public function reserveSlot(Request $request)
    {
        $request->validate([
            'drop_off_time_slot_id' => 'required|integer',
            'pick_up_time_slot_id' => 'required|integer', 
            'reservation_date' => 'required|date',
            'user_name' => 'required|string',
            'country' => 'required|string',
            'license_plate' => 'required|string',
            'vehicle_type_id' => 'required|integer',
            'email' => 'required|email'
        ]);

        $dropOffSlotId = $request->drop_off_time_slot_id;
        $pickUpSlotId = $request->pick_up_time_slot_id;
        $reservationDate = $request->reservation_date;

        // 1. Proveravamo da li su slotovi dostupni
        $availability = $this->slotService->getSlotAvailability($reservationDate, [$dropOffSlotId, $pickUpSlotId]);
        
        $dropOffRemaining = $availability[$dropOffSlotId]['remaining'] ?? 0;
        $pickUpRemaining = $availability[$pickUpSlotId]['remaining'] ?? 0;
        
        $dropOffEnabled = $availability[$dropOffSlotId]['is_enabled'] ?? false;
        $pickUpEnabled = $availability[$pickUpSlotId]['is_enabled'] ?? false;

        // 2. Ako je neki slot potpuno popunjen, vrati grešku
        if ($dropOffRemaining <= 0 || $pickUpRemaining <= 0 || !$dropOffEnabled || !$pickUpEnabled) {
            return response()->json([
                'success' => false,
                'message' => $this->getUserMessage('slot_not_available', $request)
            ], 400);
        }

        // 3. Ako je remaining = 1, kreiraj privremenu rezervaciju
        if ($dropOffRemaining === 1 || $pickUpRemaining === 1) {
            // Proveri da li već postoji aktivna rezervacija za ove slotove
            $existingReservation = TempData::where('reservation_date', $reservationDate)
                ->where(function($query) use ($dropOffSlotId, $pickUpSlotId) {
                    $query->where('drop_off_time_slot_id', $dropOffSlotId)
                          ->orWhere('pick_up_time_slot_id', $pickUpSlotId);
                })
                ->where('reserved_until', '>', now())
                ->where('status', 'reserved')
                ->first();

            if ($existingReservation) {
                return response()->json([
                    'success' => false,
                    'message' => $this->getUserMessage('slot_reserved_by_other', $request)
                ], 400);
            }

            // Kreiraj privremenu rezervaciju sa timeout-om od 10 minuta
            $merchantTransactionId = (string) \Illuminate\Support\Str::uuid();
            
            $tempReservation = TempData::create([
                'drop_off_time_slot_id' => $dropOffSlotId,
                'pick_up_time_slot_id' => $pickUpSlotId,
                'reservation_date' => $reservationDate,
                'user_name' => $request->user_name,
                'country' => $request->country,
                'license_plate' => $request->license_plate,
                'vehicle_type_id' => $request->vehicle_type_id,
                'email' => $request->email,
                'status' => 'reserved',
                'reserved_until' => now()->addMinutes(10),
                'merchant_transaction_id' => $merchantTransactionId
            ]);

            return response()->json([
                'success' => true,
                'message' => $this->getUserMessage('slot_reserved_for_you', $request),
                'reservation_id' => $tempReservation->id,
                'merchant_transaction_id' => $merchantTransactionId,
                'expires_at' => $tempReservation->reserved_until,
                'requires_payment' => true
            ]);
        }

        // 4. Ako ima više od 1 slot, dozvolj direktno kreiranje bez rezervacije
        return response()->json([
            'success' => true,
            'message' => $this->getUserMessage('slots_available', $request),
            'requires_payment' => false
        ]);
    }

    /**
     * Proveri status rezervacije slota
     */
    public function checkSlotReservation(Request $request)
    {
        $reservationId = $request->get('reservation_id');
        
        if (!$reservationId) {
            return response()->json(['success' => false, 'message' => $this->getUserMessage('reservation_id_required')]);
        }

        $tempReservation = TempData::find($reservationId);
        
        if (!$tempReservation) {
            return response()->json(['success' => false, 'message' => $this->getUserMessage('reservation_not_found')]);
        }

        if ($tempReservation->reserved_until < now()) {
            // Rezervacija je istekla
            $tempReservation->delete();
            return response()->json(['success' => false, 'message' => $this->getUserMessage('reservation_expired')]);
        }

        return response()->json([
            'success' => true,
            'expires_at' => $tempReservation->reserved_until,
            'remaining_seconds' => now()->diffInSeconds($tempReservation->reserved_until)
        ]);
    }

    /**
     * Helper function to check if a reservation combination is free
     * Free combinations:
     * 1. Same slot for arrival and departure (slots 1 or 41)
     * 2. Arrival slot 1 (00:00-07:00) and departure slot 41 (20:00-24:00)
     */
    private function isFreeReservation($dropOffSlotId, $pickUpSlotId)
    {
        // Slučaj 1: Isti slot za posebne slotove
        if ($dropOffSlotId === $pickUpSlotId && $this->allowsSameArrivalDeparture($dropOffSlotId)) {
            return true;
        }
        
        // Slučaj 2: Posebna kombinacija - dolazak slot 1 i odlazak slot 41
        if ($dropOffSlotId === 1 && $pickUpSlotId === 41) {
            return true;
        }
        
        return false;
    }

    // Prikaz svih rezervacija sa opcijom filtriranja po slot vremenu
    public function index(Request $request)
    {
        $query = Reservation::query();
        $reservations = $query->get();
        return response()->json($reservations, 200);
    }

    // Prikaz pojedinačne rezervacije po ID-u
    public function show($id)
    {
        $reservation = Reservation::findOrFail($id);
        return response()->json($reservation, 200);
    }

    /**
     * Kreiranje nove rezervacije iz TEMP_DATA koristeći merchant_transaction_id
     * Ova metoda upisuje podatke iz temp_data u reservations, a zatim briše red iz temp_data.
     * Poziva se nakon uspješnog plaćanja.
     */
    public function storeFromTemp(Request $request)
    {
        $merchantTransactionId = $request->input('merchant_transaction_id');
        $fiscalizationSuccess = $request->input('fiscalization_success', false);
        
        \Log::info('storeFromTemp - početak obrade', [
            'merchant_transaction_id' => $merchantTransactionId,
            'fiscalization_success' => $fiscalizationSuccess
        ]);
        
        if (!$merchantTransactionId) {
            return response()->json(['success' => false, 'message' => $this->getUserMessage('merchant_id_required', $request)], 422);
        }

        // Spriječi dupliranje: ako već postoji rezervacija za ovaj merchant_transaction_id, ne šalji mail i ne pravi duplikat
        $alreadyExists = Reservation::where('merchant_transaction_id', $merchantTransactionId)->first();

        if ($alreadyExists) {
            \Log::info('storeFromTemp - rezervacija već postoji', [
                'merchant_transaction_id' => $merchantTransactionId,
                'reservation_id' => $alreadyExists->id,
                'status' => $alreadyExists->status
            ]);
            return response()->json(['success' => true, 'message' => $this->getUserMessage('reservation_already_created', $request)], 200);
        }

        \Log::info('storeFromTemp - rezervacija ne postoji, nastavljam', [
            'merchant_transaction_id' => $merchantTransactionId
        ]);

        // KORISTI DATABASE TRANSACTION SA SELECT FOR UPDATE
        return DB::transaction(function () use ($merchantTransactionId, $fiscalizationSuccess, $request) {
            
            // SELECT FOR UPDATE - zaključaj temp podatke
            $temp = TempData::where('merchant_transaction_id', $merchantTransactionId)
                           ->lockForUpdate()
                           ->first();
                           
            if (!$temp) {
                \Log::error('storeFromTemp - temp podaci nisu pronađeni', [
                    'merchant_transaction_id' => $merchantTransactionId,
                    'temp_data_exists' => TempData::count(),
                    'all_temp_merchant_ids' => TempData::pluck('merchant_transaction_id')->toArray()
                ]);
                throw new Exception('Temp podaci nisu pronađeni');
            }

            \Log::info('storeFromTemp - temp podaci pronađeni i zaključani', [
                'merchant_transaction_id' => $merchantTransactionId,
                'temp_data' => $temp->toArray()
            ]);

            // DODATNA VALIDACIJA: Proveri da li je rezervacija istekla (samo za 'reserved' status)
            if ($temp->status === 'reserved' && $temp->reserved_until && $temp->reserved_until < now()) {
                \Log::error('storeFromTemp - rezervacija je istekla', [
                    'merchant_transaction_id' => $merchantTransactionId,
                    'status' => $temp->status,
                    'reserved_until' => $temp->reserved_until,
                    'current_time' => now(),
                    'minutes_expired' => $temp->reserved_until->diffInMinutes(now())
                ]);
                $temp->delete();
                throw new Exception('Rezervacija je istekla. Molimo pokušajte ponovo.');
            }



            // DODATNA VALIDACIJA: Proveri da li su temp podaci validni
            if (empty($temp->email) || empty($temp->user_name) || empty($temp->license_plate)) {
                \Log::error('storeFromTemp - temp podaci su nepotpuni', [
                    'merchant_transaction_id' => $merchantTransactionId,
                    'email' => $temp->email,
                    'user_name' => $temp->user_name,
                    'license_plate' => $temp->license_plate,
                    'temp_data_record' => $temp->toArray()
                ]);
                $temp->delete();
                throw new Exception('Temp podaci su nepotpuni');
            }

            \Log::info('storeFromTemp - temp podaci validni', [
                'merchant_transaction_id' => $merchantTransactionId,
                'email' => $temp->email,
                'user_name' => $temp->user_name,
                'license_plate' => $temp->license_plate
            ]);

            $date = $temp->reservation_date;
            $reg = $temp->license_plate;
            $dropOffSlot = $temp->drop_off_time_slot_id;
            $pickUpSlot = $temp->pick_up_time_slot_id;

            // Validacija: drop_off mora biti pre pick_up (ili isti za posebne slotove)
            if ($dropOffSlot >= $pickUpSlot && !$this->allowsSameArrivalDeparture($dropOffSlot)) {
                \Log::error('storeFromTemp - neispravni slotovi', [
                    'merchant_transaction_id' => $merchantTransactionId,
                    'drop_off_slot' => $dropOffSlot,
                    'pick_up_slot' => $pickUpSlot,
                    'allows_same' => $this->allowsSameArrivalDeparture($dropOffSlot),
                    'temp_data_record' => $temp->toArray()
                ]);
                $temp->delete();
                throw new Exception('Neispravni slotovi');
            }

            \Log::info('storeFromTemp - slotovi validni', [
                'merchant_transaction_id' => $merchantTransactionId,
                'drop_off_slot' => $dropOffSlot,
                'pick_up_slot' => $pickUpSlot
            ]);

            // Zabrani duplikat za isti dropoff slot
            $dropoffExists = Reservation::where([
                ['license_plate', $reg],
                ['reservation_date', $date],
                ['drop_off_time_slot_id', $dropOffSlot]
            ])->exists();

            if ($dropoffExists) {
                \Log::error('storeFromTemp - duplikat dropoff slot', [
                    'merchant_transaction_id' => $merchantTransactionId,
                    'license_plate' => $reg,
                    'reservation_date' => $date,
                    'drop_off_slot' => $dropOffSlot,
                    'temp_data_record' => $temp->toArray()
                ]);
                $temp->delete();
                throw new Exception('Duplikat dropoff slot');
            }

            \Log::info('storeFromTemp - nema duplikata dropoff', [
                'merchant_transaction_id' => $merchantTransactionId,
                'license_plate' => $reg,
                'reservation_date' => $date,
                'drop_off_slot' => $dropOffSlot
            ]);

            // Zabrani duplikat za isti pickup slot
            $pickupExists = Reservation::where([
                ['license_plate', $reg],
                ['reservation_date', $date],
                ['pick_up_time_slot_id', $pickUpSlot]
            ])->exists();

            if ($pickupExists) {
                \Log::error('storeFromTemp - duplikat pickup slot', [
                    'merchant_transaction_id' => $merchantTransactionId,
                    'license_plate' => $reg,
                    'reservation_date' => $date,
                    'pick_up_slot' => $pickUpSlot,
                    'temp_data_record' => $temp->toArray()
                ]);
                $temp->delete();
                throw new Exception('Duplikat pickup slot');
            }

            \Log::info('storeFromTemp - nema duplikata pickup', [
                'merchant_transaction_id' => $merchantTransactionId,
                'license_plate' => $reg,
                'reservation_date' => $date,
                'pick_up_slot' => $pickUpSlot
            ]);

            // Odredi status na osnovu uspešnosti plaćanja i fiskalizacije
            // Slučaj 1: Plaćanje uspešno + fiskalizacija uspešna → status = 'paid'
            // Slučaj 2: Plaćanje uspešno + fiskalizacija uspešna (bez fiskalnih podataka) → status = 'paid'
            // Slučaj 3: Plaćanje neuspešno → status = 'pending'
            $status = 'pending'; // default za neuspešna plaćanja
            
            // Ako je plaćanje uspešno (merchant_transaction_id postoji), status treba da bude 'paid'
            // bez obzira na uspešnost fiskalizacije
            if ($merchantTransactionId) {
                $status = 'paid';
            }

            \Log::info('storeFromTemp - određen status', [
                'merchant_transaction_id' => $merchantTransactionId,
                'fiscalization_success' => $fiscalizationSuccess,
                'status' => $status,
                'logic' => 'Plaćanje uspešno = status paid, bez obzira na fiskalizaciju'
            ]);
            
            // OSIGURAJ DA TABELA POSTOJI PRE POZIVA PROCEDURE
            $tableName = date('Ymd', strtotime($date));
            if (!$this->slotService->tableExists($tableName)) {
                \Log::info('storeFromTemp - tabela ne postoji, kreiram je', [
                    'table_name' => $tableName,
                    'date' => $date,
                    'merchant_transaction_id' => $merchantTransactionId
                ]);
                
                // Kreiraj tabelu ako ne postoji
                $this->slotService->createDynamicTable($tableName);
            }
            
            // Koristi stored proceduru umesto Eloquent save
            try {
                // Formatiraj fiscal_date ako postoji - čuvaj kao DATETIME
                $fiscalDate = null;
                if (!empty($temp->fiscal_date)) {
                    // Ako je ISO datetime string, konvertuj u DATETIME format
                    if (strpos($temp->fiscal_date, 'T') !== false) {
                        $fiscalDate = date('Y-m-d H:i:s', strtotime($temp->fiscal_date));
                    } else {
                        // Ako je samo datum, dodaj trenutno vreme
                        $fiscalDate = $temp->fiscal_date . ' ' . now()->format('H:i:s');
                    }
                }
                


            // Pripremi parametre za log
            $logParams = [
                $temp->drop_off_time_slot_id,
                $temp->pick_up_time_slot_id,
                "'" . $temp->reservation_date . "'",
                "'" . $temp->user_name . "'",
                "'" . $temp->country . "'",
                "'" . $temp->license_plate . "'",
                $temp->vehicle_type_id,
                "'" . $temp->email . "'",
                "'" . $status . "'",
                "'" . ($temp->merchant_transaction_id ?? '') . "'",
                "'" . ($temp->fiscal_jir ?? '') . "'",
                "'" . ($temp->fiscal_ikof ?? '') . "'",
                "'" . ($temp->fiscal_qr ?? '') . "'",
                "'" . ($temp->fiscal_operator ?? '') . "'",
                "'" . $fiscalDate . "'"
            ];

            \Log::info('storeFromTemp - pozivam AddReservation proceduru', [
                'merchant_transaction_id' => $merchantTransactionId,
                'status' => $status,
                'reserved_until' => $temp->reserved_until,
                'is_reservation_valid' => $temp->reserved_until ? ($temp->reserved_until > now()) : true,
                'sql_call' => 'CALL AddReservation(' . implode(', ', $logParams) . ')',
                'fiscal_data' => [
                    'jir' => $temp->fiscal_jir ?? null,
                    'ikof' => $temp->fiscal_ikof ?? null,
                    'qr' => $temp->fiscal_qr ?? null,
                    'operator' => $temp->fiscal_operator ?? null,
                    'date' => $fiscalDate
                ]
            ]);

                \DB::statement('CALL AddReservation(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                    $temp->drop_off_time_slot_id,
                    $temp->pick_up_time_slot_id,
                    $temp->reservation_date,
                    $temp->user_name,
                    $temp->country,
                    $temp->license_plate,
                    $temp->vehicle_type_id,
                    $temp->email,
                    $status, // Koristi određeni status umesto $temp->status
                    $temp->merchant_transaction_id ?? null,
                    $temp->fiscal_jir ?? null,
                    $temp->fiscal_ikof ?? null,
                    $temp->fiscal_qr ?? null,
                    $temp->fiscal_operator ?? null,
                    $fiscalDate
                ]);
                
                // Pronađi kreiranu rezervaciju za email slanje
                $reservation = Reservation::where('merchant_transaction_id', $merchantTransactionId)->first();
                if (!$reservation) {
                    \Log::error('storeFromTemp - rezervacija nije kreirana nakon AddReservation', [
                        'merchant_transaction_id' => $merchantTransactionId,
                        'temp_id' => $temp->id
                    ]);
                    // NE briši temp podatke - ostavi ih za manuelno rešavanje
                    $temp->update([
                        'status' => 'reservation_not_created',
                        'updated_at' => now()
                    ]);
                    
                    // Pošalji notifikaciju adminu o problemu
                    try {
                        $formattedSqlCall = 'CALL AddReservation(' . implode(', ', $logParams) . ')';
                        \Mail::raw("PROBLEM SA REZERVACIJOM: Rezervacija nije kreirana nakon AddReservation!\n\nMerchant Transaction ID: {$merchantTransactionId}\nTemp ID: {$temp->id}\n\nSQL poziv:\n{$formattedSqlCall}\n\nTemp podaci ostaju u bazi za manuelno rešavanje.", function ($message) {
                            $message->to('bus@kotor.me')
                                ->subject('PROBLEM: Rezervacija nije kreirana nakon AddReservation');
                        });
                    } catch (\Exception $mailException) {
                        \Log::error('Greška pri slanju notifikacije o nekreiranoj rezervaciji', ['error' => $mailException->getMessage()]);
                    }
                    
                    throw new Exception('Rezervacija nije kreirana nakon AddReservation');
                }



                \Log::info('storeFromTemp - rezervacija uspešno kreirana', [
                    'merchant_transaction_id' => $merchantTransactionId,
                    'reservation_id' => $reservation->id,
                    'status' => $reservation->status
                ]);
                
            } catch (\Exception $e) {
                \Log::error('storeFromTemp - greška pri pozivu AddReservation', [
                    'merchant_transaction_id' => $merchantTransactionId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Proveri tip greške - ako su slotovi popunjeni, NE briši temp podatke
                $errorMessage = strtolower($e->getMessage());
                $isSlotAvailabilityError = (
                    strpos($errorMessage, 'vremenski slot nije dostupan') !== false || 
                    strpos($errorMessage, 'slot nije dostupan') !== false ||
                    strpos($errorMessage, 'slot is not available') !== false ||
                    strpos($errorMessage, 'capacity exceeded') !== false ||
                    strpos($errorMessage, 'kapacitet prekoračen') !== false ||
                    strpos($errorMessage, 'no available slots') !== false ||
                    strpos($errorMessage, 'nema dostupnih slotova') !== false ||
                    strpos($errorMessage, 'slot full') !== false ||
                    strpos($errorMessage, 'slot popunjen') !== false ||
                    strpos($errorMessage, 'remaining') !== false && strpos($errorMessage, '0') !== false ||
                    // Nove poruke iz poboljšane AddReservation procedure
                    strpos($errorMessage, 'nema dostupnih mesta u slotovima') !== false ||
                    strpos($errorMessage, 'već postoji rezervacija za drop-off slot') !== false ||
                    strpos($errorMessage, 'već postoji rezervacija za pick-up slot') !== false
                );
                
                if ($isSlotAvailabilityError) {
                    \Log::error('storeFromTemp - SLOTOVI POPUNJENI - OSTAVLJAM TEMP PODATKE za manuelo rešavanje', [
                        'merchant_transaction_id' => $merchantTransactionId,
                        'temp_id' => $temp->id,
                        'error_message' => $e->getMessage(),
                        'error_type' => 'slot_availability'
                    ]);
                    
                    // Ažuriraj temp podatke sa informacijom o grešci
                    $temp->update([
                        'status' => 'paid_slots_full',
                        'updated_at' => now()
                    ]);
                    
                    // Pošalji notifikaciju adminu o problemu
                    try {
                        $formattedSqlCall = 'CALL AddReservation(' . implode(', ', $logParams) . ')';
                        \Mail::raw("PROBLEM SA REZERVACIJOM: Slotovi popunjeni nakon plaćanja!\n\nMerchant Transaction ID: {$merchantTransactionId}\nGreška: {$e->getMessage()}\n\nSQL poziv:\n{$formattedSqlCall}\n\nTemp podaci ostaju u bazi za manuelo rešavanje.", function ($message) {
                            $message->to('bus@kotor.me')
                                ->subject('PROBLEM: Slotovi popunjeni nakon plaćanja');
                        });
                    } catch (\Exception $mailException) {
                        \Log::error('Greška pri slanju notifikacije o popunjenim slotovima', ['error' => $mailException->getMessage()]);
                    }
                    
                    // ROLLBACK - vrati grešku ali sačuvaj temp podatke
                    throw new Exception('Slotovi popunjeni nakon plaćanja');
                    
                } else {
                    // Za druge greške, obriši temp podatke
                    \Log::info('storeFromTemp - brišem temp podatke zbog druge greške', [
                        'merchant_transaction_id' => $merchantTransactionId,
                        'error_type' => 'not_slot_availability'
                    ]);
                    $temp->delete();
                    throw new Exception('Greška pri kreiranju rezervacije: ' . $e->getMessage());
                }
            }

            \Log::info('storeFromTemp - označavam temp podatke kao obrađene', [
                'merchant_transaction_id' => $merchantTransactionId,
                'temp_id' => $temp->id
            ]);
            
            // SIGURNOST: Označi temp podatke kao obrađene umesto brisanja
            // Ovi podaci se koriste u admin panelu za pregled neuspješnih plaćanja
            $temp->update([
                'status' => 'processed',
                'updated_at' => now()
            ]);
            
            // Proveri da li se rezervacija kreirala sa email_sent = 0
            $createdReservation = \App\Models\Reservation::where('merchant_transaction_id', $merchantTransactionId)->first();

            \Log::info('storeFromTemp - provera kreirane rezervacije', [
                'merchant_transaction_id' => $merchantTransactionId,
                'reservation_found' => $createdReservation ? true : false,
                'reservation_id' => $createdReservation ? $createdReservation->id : null,
                'reservation_status' => $createdReservation ? $createdReservation->status : null
            ]);

            // Sačuvaj ID rezervacije i merchant_transaction_id u sesiju za success stranicu
            if ($createdReservation) {
                session([
                    'last_reservation_id' => $createdReservation->id,
                    'last_merchant_transaction_id' => $merchantTransactionId
                ]);
                
                // SAMO AKO JE REZERVACIJA USPEŠNO KREIRANA - obriši temp podatke
                // (email se šalje sa podacima iz reservations tabele, temp_data nije potreban)
                \Log::info('storeFromTemp - rezervacija uspešno kreirana, brišem temp podatke', [
                    'merchant_transaction_id' => $merchantTransactionId,
                    'reservation_id' => $createdReservation->id,
                    'temp_id' => $temp->id
                ]);
                $temp->delete();
            } else {
                // Ako rezervacija nije kreirana, zadrži temp podatke za admin panel
                // i označi ih kao problematične
                $temp->update([
                    'status' => 'failed',
                    'updated_at' => now()
                ]);
                \Log::warning('storeFromTemp - rezervacija nije kreirana, označavam temp podatke kao failed', [
                    'merchant_transaction_id' => $merchantTransactionId,
                    'temp_id' => $temp->id
                ]);
            }
            
            \Log::info('storeFromTemp - završeno uspešno', [
                'merchant_transaction_id' => $merchantTransactionId,
                'reservation_id' => $createdReservation ? $createdReservation->id : null
            ]);
            
            \Log::info('storeFromTemp - vraćam OK response', [
                'merchant_transaction_id' => $merchantTransactionId
            ]);
            
            return response('OK', 200);
            
        }, 5); // 5 pokušaja za retry ako dođe do deadlock-a
    }

    // Kreiranje nove rezervacije (klasičan način, direktan upis)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'drop_off_time_slot_id' => 'required|integer|exists:list_of_time_slots,id',
            'pick_up_time_slot_id'  => 'required|integer|exists:list_of_time_slots,id',
            'reservation_date'      => 'required|date',
            'user_name'             => 'required|string|max:255',
            'country'               => 'required|string|max:100',
            'license_plate'         => 'required|string|max:20',
            'vehicle_type_id'       => 'required|integer|exists:vehicle_types,id',
            'email'                 => 'required|email|max:255',
            'status'                => 'sometimes|string|in:pending,paid,free',
            'merchant_transaction_id' => 'sometimes|string|max:64',
            'fiscal_jir'            => 'sometimes|string|max:64|nullable',
            'fiscal_ikof'           => 'sometimes|string|max:64|nullable',
            'fiscal_qr'             => 'sometimes|string|max:255|nullable',
            'fiscal_operator'       => 'sometimes|string|max:64|nullable',
            'fiscal_date'           => 'sometimes|string|max:32|nullable',
            'email_sent'            => 'sometimes|boolean',
        ]);

        $date = $validated['reservation_date'];
        $reg = $validated['license_plate'];
        $dropOffSlot = $validated['drop_off_time_slot_id'];
        $pickUpSlot = $validated['pick_up_time_slot_id'];

        // Proveri da li je besplatna rezervacija i postavi status
        $isFree = $this->isFreeReservation($dropOffSlot, $pickUpSlot);
        if ($isFree) {
            $validated['status'] = 'free';
        }

        // Dodaj ovu proveru
        if ($dropOffSlot >= $pickUpSlot && !$this->allowsSameArrivalDeparture($dropOffSlot)) {
            return response()->json([
                'success' => false,
                'message' => $this->getUserMessage('invalid_slot_order', $request)
            ], 422);
        }

        // NOVA PROVERA: Proveri dostupnost slotova u dinamičkim tabelama (i za besplatne rezervacije)
        $availability = $this->slotService->getSlotAvailability($date, [$dropOffSlot, $pickUpSlot]);
        
        $dropOffRemaining = $availability[$dropOffSlot]['remaining'] ?? 0;
        $pickUpRemaining = $availability[$pickUpSlot]['remaining'] ?? 0;
        
        $dropOffEnabled = $availability[$dropOffSlot]['is_enabled'] ?? false;
        $pickUpEnabled = $availability[$pickUpSlot]['is_enabled'] ?? false;

        // Blokiraj ako nema dostupnih mesta
        if ($dropOffRemaining <= 0 || $pickUpRemaining <= 0 || !$dropOffEnabled || !$pickUpEnabled) {
            return response()->json([
                'success' => false,
                'message' => $this->getUserMessage('slots_not_available_store', $request)
            ], 400);
        }

        // Upozorenje za admin ako su slotovi kritični (remaining = 1)
        $warningMessage = '';
        if ($dropOffRemaining === 1 || $pickUpRemaining === 1) {
            $warningMessage = ' PAŽNJA: Rezervišete poslednji dostupan slot za ovaj termin.';
        }

        // Zabrani duplikat za isti dropoff slot SA ZAKLJUČAVANJEM
        $dropoffExists = Reservation::where([
            ['license_plate', $reg],
            ['reservation_date', $date],
            ['drop_off_time_slot_id', $dropOffSlot]
        ])->lockForUpdate()->exists();

        if ($dropoffExists) {
            return response()->json([
                'success' => false,
                'message' => $this->getUserMessage('duplicate_reservation_dropoff', $request)
            ], 422);
        }

        // Zabrani duplikat za isti pickup slot SA ZAKLJUČAVANJEM
        $pickupExists = Reservation::where([
            ['license_plate', $reg],
            ['reservation_date', $date],
            ['pick_up_time_slot_id', $pickUpSlot]
        ])->lockForUpdate()->exists();

        if ($pickupExists) {
            return response()->json([
                'success' => false,
                'message' => $this->getUserMessage('duplicate_reservation_pickup', $request)
            ], 422);
        }

        // POSTAVI TIMEOUT ZA TRANSAKCIJU
        DB::statement('SET SESSION innodb_lock_wait_timeout = 10');
        
        // OSIGURAJ DA TABELA POSTOJI PRE POZIVA PROCEDURE
        $tableName = date('Ymd', strtotime($date));
        \Log::info('ReservationController store - proveravam tabelu', [
            'table_name' => $tableName,
            'date' => $date
        ]);
        
        if (!$this->slotService->tableExists($tableName)) {
            \Log::info('ReservationController - tabela ne postoji, kreiram je', [
                'table_name' => $tableName,
                'date' => $date
            ]);
            
            // Kreiraj tabelu ako ne postoji
            $this->slotService->createDynamicTable($tableName);
        } else {
            \Log::info('ReservationController - tabela već postoji', [
                'table_name' => $tableName,
                'date' => $date
            ]);
        }
        
        // Pozovi stored proceduru (ona brine o dostupnosti i ažuriranju slotova)
        // Status logika:
        // - 'free' - za rezervacije bez plaćanja (ručno kreirane)
        // - 'pending' - za rezervacije sa plaćanjem ali bez fiskalizacije
        // - 'paid' - za rezervacije sa uspešnim plaćanjem i fiskalizacijom
        \Log::info('ReservationController store - pozivam AddReservation proceduru', [
            'drop_off_slot' => $validated['drop_off_time_slot_id'],
            'pick_up_slot' => $validated['pick_up_time_slot_id'],
            'date' => $validated['reservation_date'],
            'license_plate' => $validated['license_plate'],
            'status' => $validated['status'] ?? 'free'
        ]);
        
        try {
            // Formatiraj fiscal_date ako postoji - čuvaj kao DATETIME
            $fiscalDate = null;
            if (!empty($validated['fiscal_date'])) {
                // Ako je ISO datetime string, konvertuj u DATETIME format
                if (strpos($validated['fiscal_date'], 'T') !== false) {
                    $fiscalDate = date('Y-m-d H:i:s', strtotime($validated['fiscal_date']));
                } else {
                    // Ako je samo datum, dodaj trenutno vreme
                    $fiscalDate = $validated['fiscal_date'] . ' ' . now()->format('H:i:s');
                }
            }
            
            \DB::statement('CALL AddReservation(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
                $validated['drop_off_time_slot_id'],
                $validated['pick_up_time_slot_id'],
                $validated['reservation_date'],
                $validated['user_name'],
                $validated['country'],
                $validated['license_plate'],
                $validated['vehicle_type_id'],
                $validated['email'],
                $validated['status'] ?? 'free', // Default 'free' za ručno kreirane rezervacije
                $validated['merchant_transaction_id'] ?? null,
                $validated['fiscal_jir'] ?? null,
                $validated['fiscal_ikof'] ?? null,
                $validated['fiscal_qr'] ?? null,
                $validated['fiscal_operator'] ?? null,
                $fiscalDate
            ]);
            
            \Log::info('ReservationController store - AddReservation procedura uspešno izvršena');
            
        } catch (\Exception $e) {
            \Log::error('ReservationController store - greška pri pozivu AddReservation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        // Za besplatne rezervacije, pošalji email sa računom
        if (($validated['status'] ?? 'free') === 'free') {
            
            try {
                        // Pronađi kreiranu rezervaciju
        $reservation = Reservation::where([
            ['license_plate', $validated['license_plate']],
            ['reservation_date', $validated['reservation_date']],
            ['drop_off_time_slot_id', $validated['drop_off_time_slot_id']],
            ['pick_up_time_slot_id', $validated['pick_up_time_slot_id']]
        ])->latest()->first();



                if ($reservation && $reservation->email) {
                    // Za besplatne rezervacije koristi posebnu potvrdu
                    // Uzmi jezik iz temp podataka ako postoji, inače koristi default
                    $userLanguage = 'en'; // default
                    if ($reservation->merchant_transaction_id) {
                        $temp = \App\Models\TempData::where('merchant_transaction_id', $reservation->merchant_transaction_id)->first();
                        if ($temp && $temp->user_language) {
                            $userLanguage = $temp->user_language;
                        }
                    }
                    $pdfContent = $this->generateFreeReservationConfirmationPdf($reservation, $userLanguage);
                    
                    // ATOMIČNA PROVERA: Proveri i ažuriraj email_sent u jednoj SQL komandi
                    $rowsAffected = \DB::table('reservations')
                        ->where('id', $reservation->id)
                        ->where('email_sent', 0) // Samo ako nije poslat
                        ->update(['email_sent' => 1]);
                    
                    if ($rowsAffected > 0) {
                        // Mail nije bio poslat, sada ga pošalji
                        // Uzmi jezik iz request-a ili koristi default
                        $userLanguage = $request->input('user_language', 'en');
                        
                        try {
                            Mail::to($reservation->email)->send(
                                new \App\Mail\FreeReservationConfirmationMail(
                                    $reservation->user_name,
                                    $pdfContent,
                                    $userLanguage
                                )
                            );
                        } catch (\Exception $e) {
                            \Log::error('Greška pri slanju email-a za besplatnu rezervaciju: ' . $e->getMessage());
                            // Ne vraćamo grešku jer rezervacija je već kreirana
                        }
                    }
                }
            } catch (\Exception $e) {
                // Loguj grešku za debugging
                \Log::error('Greška pri slanju email-a za besplatnu rezervaciju: ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());
                
                // Vraćamo grešku frontend-u da korisnik zna šta se desilo
                return response()->json([
                    'success' => false,
                    'message' => $this->getUserMessage('free_reservation_email_error', $request) . $e->getMessage()
                ], 500);
            }
        }

        // Pronađi kreiranu rezervaciju za vraćanje ID-a
        $reservation = Reservation::where([
            ['license_plate', $validated['license_plate']],
            ['reservation_date', $validated['reservation_date']],
            ['drop_off_time_slot_id', $validated['drop_off_time_slot_id']],
            ['pick_up_time_slot_id', $validated['pick_up_time_slot_id']]
        ])->latest()->first();

        $message = $this->getUserMessage('free_reservation_successful', $request);
        
        $response = [
            'success' => true,
            'message' => $message . $warningMessage,
            'id' => $reservation->id ?? null,
            'email' => $validated['email'],
            'warning' => !empty($warningMessage)
        ];
        
        \Log::info('ReservationController store - vraćam response', [
            'response' => $response,
            'reservation_id' => $reservation->id ?? null
        ]);
        
        return response()->json($response, 200);
    }

    // Rezervacija iz frontenda (možeš koristiti samo store, nema potrebe za duplikatom!)
    public function reserve(Request $request)
    {
        return $this->store($request);
    }

    // Slanje računa korisniku na email (ručno - za rezervacije sa statusom 'paid')
    public function sendInvoiceToUser($id)
    {
        $reservation = Reservation::findOrFail($id);
        
        // Postavi default jezik pošto smo odustali od višejezičnosti
        $userLanguage = 'en';
        
        try {
            $invoicePdf = $this->generateInvoicePdf($reservation, $userLanguage);
        } catch (\Exception $e) {
            \Log::error('Greška pri generisanju PDF-a: ' . $e->getMessage());
            // Fallback na default jezik
            $invoicePdf = $this->generateInvoicePdf($reservation, 'en');
        }

        Mail::to($reservation->email)->send(
            new \App\Mail\PaymentReservationConfirmationMail(
                $reservation->user_name,
                $invoicePdf,
                null // Treći argument za kompatibilnost sa serverom
            )
        );

        return response()->json(['success' => 'Invoice sent to user email.']);
    }

    // DODATNA METODA: Ručno slanje email-a sa default jezikom
    public function sendInvoiceManually($id)
    {
        $reservation = Reservation::findOrFail($id);
        
        try {
            $userLanguage = 'en';
            $invoicePdf = $this->generateInvoicePdf($reservation, $userLanguage);
            
            Mail::to($reservation->email)->send(
                new \App\Mail\PaymentReservationConfirmationMail(
                    $reservation->user_name,
                    $invoicePdf,
                    null,
                    false,
                    $userLanguage
                )
            );
            
            // Označi da je email poslat
            if (\Schema::hasColumn('reservations', 'email_sent')) {
                $reservation->update(['email_sent' => 1]);
            }
            
            \Log::info('Manual email sent successfully', [
                'reservation_id' => $reservation->id,
                'email' => $reservation->email
            ]);
            
            return response()->json(['success' => 'Email sent successfully.']);
        } catch (\Exception $e) {
            \Log::error('Manual email sending failed', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json(['error' => 'Failed to send email: ' . $e->getMessage()], 500);
        }
    }

    // Ažuriranje rezervacije (npr. postavi status na paid i šalji mail)
    public function update(Request $request, $id)
    {
        $reservation = Reservation::findOrFail($id);

        $validated = $request->validate([
            'drop_off_time_slot_id' => 'sometimes|required|integer|exists:list_of_time_slots,id',
            'pick_up_time_slot_id'  => 'sometimes|required|integer|exists:list_of_time_slots,id',
            'reservation_date'      => 'sometimes|required|date',
            'user_name'             => 'sometimes|required|string|max:255',
            'country'               => 'sometimes|required|string|max:100',
            'license_plate'         => 'sometimes|required|string|max:20',
            'vehicle_type_id'       => 'sometimes|required|integer|exists:vehicle_types,id',
            'email'                 => 'sometimes|required|email|max:255',
            'status'                => 'sometimes|required|string|in:pending,paid,free',
            'merchant_transaction_id' => 'sometimes|string|max:64',
            'fiscal_jir'            => 'sometimes|string|max:64|nullable',
            'fiscal_ikof'           => 'sometimes|string|max:64|nullable',
            'fiscal_qr'             => 'sometimes|string|max:255|nullable',
            'fiscal_operator'       => 'sometimes|string|max:64|nullable',
            'fiscal_date'           => 'sometimes|string|max:32|nullable',
        ]);

        $reservation->update($validated);

        // Šalji mail kad je status promijenjen u 'paid' (uspešna fiskalizacija)
        // SAMO ako status nije već bio 'paid' (da izbegnemo duplikate)
        if (
            isset($validated['status']) &&
            $validated['status'] === 'paid' &&
            $reservation->getOriginal('status') !== 'paid' &&
            $reservation->email
        ) {
            // DODATNA VALIDACIJA: Proveri da li je email već poslat
            if (\Schema::hasColumn('reservations', 'email_sent') && $reservation->email_sent) {
                \Log::info('update - email već poslat za rezervaciju', [
                    'reservation_id' => $reservation->id,
                    'email' => $reservation->email
                ]);
            } else {
                $userName = $reservation->user_name;
                // Uzmi jezik iz temp podataka ako postoji, inače koristi default
                $userLanguage = 'en'; // default
                if ($reservation->merchant_transaction_id) {
                    $temp = \App\Models\TempData::where('merchant_transaction_id', $reservation->merchant_transaction_id)->first();
                    if ($temp && $temp->user_language) {
                        $userLanguage = $temp->user_language;
                    }
                }
                
                try {
                    $invoicePdf = $this->generateInvoicePdf($reservation, $userLanguage);
                } catch (\Exception $e) {
                    \Log::error('Greška pri generisanju PDF-a: ' . $e->getMessage());
                    // Fallback na default jezik
                    $invoicePdf = $this->generateInvoicePdf($reservation, 'en');
                }

                Mail::to($reservation->email)->send(
                    new \App\Mail\PaymentReservationConfirmationMail($userName, $invoicePdf, null, false, $userLanguage)
                );

                // Označi da je email poslat
                if (\Schema::hasColumn('reservations', 'email_sent')) {
                    $reservation->update(['email_sent' => 1]);
                }
            }
        }

        return response()->json(['success' => 'Reservation updated successfully.']);
    }

    public function destroy($id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->delete();

        return response()->json(['message' => 'Reservation deleted successfully'], 200);
    }

    public function byDate(Request $request)
    {
        $date = $request->query('date');
        if (!$date) {
            return response()->json(['error' => 'Date parameter is required.'], 400);
        }

        $reservations = Reservation::whereDate('reservation_date', $date)->get();

        return response()->json($reservations);
    }

    public function showSlots(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        $slots = $this->slotService->getSlotsForDate($date);
        return response()->json($slots);
    }

    // --- PDF GENERATORI ---

    public function generateInvoicePdf($reservation, $userLanguage = 'en')
    {
        // Validacija jezika
        if (!in_array($userLanguage, ['en', 'me'])) {
            $userLanguage = 'en';
        }
        
        // Generiši QR kod samo ako postoji podatak
        $qrBase64 = null;
        if ($reservation->fiscal_qr) {
            $qrCode = new QrCode($reservation->fiscal_qr);
            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            $qrBase64 = $result->getDataUri();
        }

        return Pdf::loadView('pdfs.invoice_pdf', [
            'reservation' => $reservation,
            'qrBase64' => $qrBase64,
            'user_language' => $userLanguage
        ])->output();
    }

    public function generateFreeReservationConfirmationPdf($reservation, $userLanguage = 'en')
    {
        // Validacija jezika
        if (!in_array($userLanguage, ['en', 'me'])) {
            $userLanguage = 'en';
        }
        
        return Pdf::loadView('pdfs.free_reservation_confirmation', [
            'reservation' => $reservation,
            'user_language' => $userLanguage
        ])->output();
    }

    
    public function stornoFiskalniRacun($id)
    {
        $reservation = Reservation::findOrFail($id);

        // Pronađi tip vozila
        $vehicleType = $reservation->vehicleType;
        $price = $vehicleType ? $vehicleType->price : 0;
        $descriptionVehicle = $vehicleType ? $vehicleType->description_vehicle : '';

        // Pripremi podatke za korektivni račun
        $originalData = [
            'originalUID' => $reservation->fiscal_ikof,
            'originalIssueDate' => $reservation->fiscal_date,
            'originalTotal' => $price,
            'vehicleTypeId' => $reservation->vehicle_type_id,
            'price' => $price,
            'description_vehicle' => $descriptionVehicle
        ];

        $fiskalController = app(\App\Http\Controllers\FiskalController::class);
        $result = $fiskalController->cancelReceipt($reservation->merchant_transaction_id, $originalData);

        if ($result['success']) {
            // Po želji: ažuriraj status rezervacije, upiši podatke o storno fiskalizaciji
            $reservation->status = 'storno';
            $reservation->save();
            
            return response()->json(['success' => true, 'message' => $this->getUserMessage('fiscal_cancellation_started')]);
        } else {
            return response()->json(['success' => false, 'message' => $result['error'] ?? $this->getUserMessage('fiscal_cancellation_error')]);
        }
    }

    public function slotCount(Request $request)
    {
        $date = $request->query('date');
        $slotId = $request->query('slot_id');
        $type = $request->query('type', 'drop_off'); // 'drop_off' ili 'pick_up'

        if (!$date || !$slotId) {
            return response()->json(['count' => 0]);
        }

        // ISPRAVKA: Koristi dinamičke tabele umesto reservations tabele
        $tableName = date('Ymd', strtotime($date));
        
        try {
            // Proveri da li dinamička tabela postoji
            $tableExists = \DB::select("SHOW TABLES LIKE '{$tableName}'");
            if (!$tableExists) {
                return response()->json(['count' => 0]);
            }

            // Uzmi podatke iz dinamičke tabele
            $slot = \DB::table($tableName)
                ->where('time_slot_id', $slotId)
                ->first();

            if (!$slot) {
                return response()->json(['count' => 0]);
            }

            // Izračunaj broj rezervacija na osnovu remaining
            // Dohvati maksimalni kapacitet iz system_config tabele (cache-ovano)
            $defaultValue = config('app.default_available_parking_slots', 8);
            $availableParkingSlots = cache()->remember('available_parking_slots', 300, function () use ($defaultValue) {
                return \App\Models\SystemConfig::where('name', 'available_parking_slots')->value('value') ?? $defaultValue;
            });
            $maxCapacity = ($slotId == 1 || $slotId == 41) ? 999 : $availableParkingSlots;
            
            // Posebno rukovanje za slot 1 i 41 koji imaju veliku vrednost
            if ($slot->remaining > 900) {
                // Specijalni slotovi, verovatno nema ograničenja
                $count = 0;
            } else {
                // Regularan slot: count = max_capacity - remaining
                $count = $maxCapacity - $slot->remaining;
            }

            return response()->json([
                'count' => $count,
                'remaining' => $slot->remaining,
                'available' => (bool)$slot->available,
                'max_capacity' => $maxCapacity,
                'table_used' => $tableName
            ]);

        } catch (\Exception $e) {
            \Log::error("Error getting slot count from dynamic table: " . $e->getMessage());
            
            // Fallback na stari sistem ako dinamička tabela ne radi
            $query = Reservation::whereDate('reservation_date', $date);

            if ($type === 'drop_off') {
                $query->where('drop_off_time_slot_id', $slotId);
            } else {
                $query->where('pick_up_time_slot_id', $slotId);
            }

            $count = $query->count();

            return response()->json([
                'count' => $count,
                'fallback' => true,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Šalje potvrdu o besplatnoj rezervaciji na email
     */
    public function sendFreeConfirmation(Request $request)
    {
        $request->validate([
            'reservation_id' => 'required|integer|exists:reservations,id',
            'email' => 'required|email'
        ]);

        try {
            $reservation = Reservation::with(['vehicleType', 'dropOffTimeSlot', 'pickUpTimeSlot'])->findOrFail($request->reservation_id);
            
            // Provera da li je rezervacija besplatna
            if ($reservation->status !== 'free') {
                return response()->json(['success' => false, 'message' => $this->getUserMessage('reservation_not_free')], 400);
            }

            // Provera da li je email već poslan
            if ($reservation->email_sent) {
                return response()->json(['success' => false, 'message' => $this->getUserMessage('email_already_sent')], 400);
            }

            // Generisanje PDF-a
            // Uzmi jezik iz temp podataka ako postoji, inače koristi default
            $userLanguage = 'en'; // default
            if ($reservation->merchant_transaction_id) {
                $temp = \App\Models\TempData::where('merchant_transaction_id', $reservation->merchant_transaction_id)->first();
                if ($temp && $temp->user_language) {
                    $userLanguage = $temp->user_language;
                }
            }
            $pdfContent = $this->generateFreeReservationConfirmationPdf($reservation, $userLanguage);

            // Slanje email-a
            // Uzmi jezik iz request-a ili koristi default
            $userLanguage = $request->input('user_language', 'en');
            
            try {
                Mail::to($request->email)->send(
                    new \App\Mail\FreeReservationConfirmationMail(
                        $reservation->user_name,
                        $pdfContent,
                        $userLanguage
                    )
                );
            } catch (\Exception $e) {
                \Log::error('Greška pri slanju potvrde o besplatnoj rezervaciji: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => $this->getUserMessage('email_sending_error')], 500);
            }

            // Označavanje da je email poslan
            $reservation->update(['email_sent' => true]);

            return response()->json(['success' => true, 'message' => $this->getUserMessage('confirmation_sent_successfully')]);

        } catch (\Exception $e) {
            \Log::error('Greška pri slanju potvrde o besplatnoj rezervaciji: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $this->getUserMessage('email_sending_error')], 500);
        }
    }
}