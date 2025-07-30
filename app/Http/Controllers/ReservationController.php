<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\TempData;
use App\Models\TimeSlot;
use App\Services\SlotService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentReservationConfirmationMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class ReservationController extends Controller
{
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
        
        if (!$merchantTransactionId) {
            return response()->json(['success' => false, 'message' => 'merchant_transaction_id je obavezan!'], 422);
        }

        // Spriječi dupliranje: ako već postoji rezervacija za ovaj merchant_transaction_id, ne šalji mail i ne pravi duplikat
        $alreadyExists = Reservation::where('merchant_transaction_id', $merchantTransactionId)->first();

        if ($alreadyExists) {
            return response()->json(['success' => true, 'message' => 'Reservation already created.'], 200);
        }

        $temp = TempData::where('merchant_transaction_id', $merchantTransactionId)->first();
        if (!$temp) {
            return response()->json(['success' => false, 'message' => 'Privremeni podaci nisu pronađeni.'], 404);
        }

        $date = $temp->reservation_date;
        $reg = $temp->license_plate;
        $dropOffSlot = $temp->drop_off_time_slot_id;
        $pickUpSlot = $temp->pick_up_time_slot_id;

        // Validacija: drop_off mora biti pre pick_up (ili isti za posebne slotove)
        if ($dropOffSlot >= $pickUpSlot && !$this->allowsSameArrivalDeparture($dropOffSlot)) {
            $temp->delete();
            return response()->json([
                'success' => false,
                'message' => 'Drop-off slot mora biti pre pick-up slota (drop_off_time_slot_id < pick_up_time_slot_id).'
            ], 422);
        }

        // Zabrani duplikat za isti dropoff slot
        $dropoffExists = Reservation::where([
            ['license_plate', $reg],
            ['reservation_date', $date],
            ['drop_off_time_slot_id', $dropOffSlot]
        ])->exists();

        if ($dropoffExists) {
            $temp->delete();
            return response()->json([
                'success' => false,
                'message' => "Već postoji rezervacija za ovu registarsku oznaku, slot i dan (drop-off)."
            ], 422);
        }

        // Zabrani duplikat za isti pickup slot
        $pickupExists = Reservation::where([
            ['license_plate', $reg],
            ['reservation_date', $date],
            ['pick_up_time_slot_id', $pickUpSlot]
        ])->exists();

        if ($pickupExists) {
            $temp->delete();
            return response()->json([
                'success' => false,
                'message' => "Već postoji rezervacija za ovu registarsku oznaku, slot i dan (pick-up)."
            ], 422);
        }

        // Odredi status na osnovu uspešnosti fiskalizacije
        $status = 'pending'; // default
        if ($fiscalizationSuccess) {
            $status = 'paid';
        }
        
        // Koristi stored proceduru umesto Eloquent save
        try {
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
                $temp->fiscal_date ?? null
            ]);
            
            // Pronađi kreiranu rezervaciju za email slanje
            $reservation = Reservation::where('merchant_transaction_id', $merchantTransactionId)->first();
            if (!$reservation) {
                $temp->delete(); // Očisti temp podatke
                return response()->json(['success' => false, 'message' => 'Rezervacija nije kreirana.'], 500);
            }
            
        } catch (\Exception $e) {
            $temp->delete(); // Očisti temp podatke u slučaju greške
            return response()->json(['success' => false, 'message' => 'Greška pri čuvanju rezervacije: ' . $e->getMessage()], 500);
        }

        $temp->delete();

        // Proveri da li se rezervacija kreirala sa email_sent = 0
        $createdReservation = \App\Models\Reservation::where('merchant_transaction_id', $merchantTransactionId)->first();

        // Sačuvaj ID rezervacije u sesiju za success stranicu
        session(['last_reservation_id' => $createdReservation->id]);
        
        return response('OK', 200);
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
                'message' => 'Drop-off slot mora biti pre pick-up slota (drop_off_time_slot_id < pick_up_time_slot_id).'
            ], 422);
        }

        // Zabrani duplikat za isti dropoff slot
        $dropoffExists = Reservation::where([
            ['license_plate', $reg],
            ['reservation_date', $date],
            ['drop_off_time_slot_id', $dropOffSlot]
        ])->exists();

        if ($dropoffExists) {
            return response()->json([
                'success' => false,
                'message' => "Već postoji rezervacija za ovu registarsku oznaku, slot i dan (drop-off)."
            ], 422);
        }

        // Zabrani duplikat za isti pickup slot
        $pickupExists = Reservation::where([
            ['license_plate', $reg],
            ['reservation_date', $date],
            ['pick_up_time_slot_id', $pickUpSlot]
        ])->exists();

        if ($pickupExists) {
            return response()->json([
                'success' => false,
                'message' => "Već postoji rezervacija za ovu registarsku oznaku, slot i dan (pick-up)."
            ], 422);
        }

        // Pozovi stored proceduru (ona brine o dostupnosti i ažuriranju slotova)
        // Status logika:
        // - 'free' - za rezervacije bez plaćanja (ručno kreirane)
        // - 'pending' - za rezervacije sa plaćanjem ali bez fiskalizacije
        // - 'paid' - za rezervacije sa uspešnim plaćanjem i fiskalizacijom
        try {
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
                $validated['fiscal_date'] ?? null
            ]);
            
        } catch (\Exception $e) {
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
                    $pdfContent = $this->generateFreeReservationConfirmationPdf($reservation);
                    
                    // ATOMIČNA PROVERA: Proveri i ažuriraj email_sent u jednoj SQL komandi
                    $rowsAffected = \DB::table('reservations')
                        ->where('id', $reservation->id)
                        ->where('email_sent', 0) // Samo ako nije poslat
                        ->update(['email_sent' => 1]);
                    
                    if ($rowsAffected > 0) {
                        // Mail nije bio poslat, sada ga pošalji
                        Mail::to($reservation->email)->send(
                            new \App\Mail\PaymentReservationConfirmationMail(
                                $reservation->user_name,
                                $pdfContent,
                                null
                            )
                        );
                    }
                }
            } catch (\Exception $e) {
                // Ne vraćamo grešku jer je rezervacija već kreirana
            }
        }

        return response('OK', 200);
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

        if (!$reservation->email) {
            return response()->json(['error' => 'Email adresa nije pronađena za ovu rezervaciju.'], 422);
        }

        $invoicePdf = $this->generateInvoicePdf($reservation);

        Mail::to($reservation->email)->send(
            new \App\Mail\PaymentReservationConfirmationMail(
                $reservation->user_name,
                $invoicePdf,
                null // Treći argument za kompatibilnost sa serverom
            )
        );

        return response()->json(['success' => 'Invoice sent to user email.']);
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
            $userName = $reservation->user_name;
            $invoicePdf = $this->generateInvoicePdf($reservation);

            Mail::to($reservation->email)->send(
                new \App\Mail\PaymentReservationConfirmationMail($userName, $invoicePdf, null)
            );
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

    public function generateInvoicePdf($reservation)
    {
        // Generiši QR kod samo ako postoji podatak
        $qrBase64 = null;
        if ($reservation->fiscal_qr) {
            $qrCode = new QrCode($reservation->fiscal_qr);
            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            $qrBase64 = $result->getDataUri();
        }

        return Pdf::loadView('pdfs/invoice_pdf', [
            'reservation' => $reservation,
            'qrBase64' => $qrBase64
        ])->output();
    }

    public function generateFreeReservationConfirmationPdf($reservation)
    {
        return Pdf::loadView('pdfs.free_reservation_confirmation', [
            'reservation' => $reservation
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
            
            return response()->json(['success' => true, 'message' => 'Storniranje fiskalnog računa je uspešno pokrenuto.']);
        } else {
            return response()->json(['success' => false, 'message' => $result['error'] ?? 'Greška pri storniranju.']);
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

        $query = Reservation::whereDate('reservation_date', $date);

        if ($type === 'drop_off') {
            $query->where('drop_off_time_slot_id', $slotId);
        } else {
            $query->where('pick_up_time_slot_id', $slotId);
        }

        $count = $query->count();

        return response()->json(['count' => $count]);
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
                return response()->json(['success' => false, 'message' => 'Rezervacija nije besplatna.'], 400);
            }

            // Provera da li je email već poslan
            if ($reservation->email_sent) {
                return response()->json(['success' => false, 'message' => 'Email je već poslan.'], 400);
            }

            // Generisanje PDF-a
            $pdf = $this->generateFreeReservationConfirmationPdf($reservation);
            $pdfContent = $pdf->output();

            // Slanje email-a
            Mail::send('emails.blank', [
                'content' => 'U prilogu se nalazi potvrda o besplatnoj rezervaciji parkinga.'
            ], function ($message) use ($request, $pdfContent, $reservation) {
                $message->to($request->email)
                        ->subject('Potvrda besplatne rezervacije parkinga - Opština Kotor')
                        ->attachData($pdfContent, 'potvrda_besplatne_rezervacije.pdf', [
                            'mime' => 'application/pdf',
                        ]);
            });

            // Označavanje da je email poslan
            $reservation->update(['email_sent' => true]);

            return response()->json(['success' => true, 'message' => 'Potvrda je uspešno poslana na email.']);

        } catch (\Exception $e) {
            \Log::error('Greška pri slanju potvrde o besplatnoj rezervaciji: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Greška pri slanju email-a.'], 500);
        }
    }
}