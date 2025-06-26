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

class ReservationController extends Controller
{
    protected $slotService;

    public function __construct(SlotService $slotService)
    {
        $this->slotService = $slotService;
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
        \Log::info('storeFromTemp pozvan', ['request' => $request->all()]);
        $merchantTransactionId = $request->input('merchant_transaction_id');
        if (!$merchantTransactionId) {
            return response()->json(['success' => false, 'message' => 'merchant_transaction_id je obavezan!'], 422);
        }

        $temp = TempData::where('merchant_transaction_id', $merchantTransactionId)->first();
        if (!$temp) {
            return response()->json(['success' => false, 'message' => 'Privremeni podaci nisu pronađeni.'], 404);
        }

        $date = $temp->reservation_date;
        $reg = $temp->license_plate;
        $dropOffSlot = $temp->drop_off_time_slot_id;
        $pickUpSlot = $temp->pick_up_time_slot_id;

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

        // Pozovi stored proceduru (ona brine o dostupnosti i ažuriranju slotova)
        try {
            \DB::statement('CALL AddReservation(?, ?, ?, ?, ?, ?, ?, ?)', [
                $temp->drop_off_time_slot_id,
                $temp->pick_up_time_slot_id,
                $temp->reservation_date,
                $temp->user_name,
                $temp->country,
                $temp->license_plate,
                $temp->vehicle_type_id,
                $temp->email
            ]);
        } catch (\Exception $e) {

                \Log::error('Greška u AddReservation proceduri preko storeFromTemp', [
                'merchantTransactionId' => $merchantTransactionId,
                'message' => $e->getMessage()
            ]);
            $temp->delete();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $temp->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reservation created successfully'
        ], 201);
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
            'status'                => 'sometimes|string|in:pending,paid',
        ]);

        $date = $validated['reservation_date'];
        $reg = $validated['license_plate'];
        $dropOffSlot = $validated['drop_off_time_slot_id'];
        $pickUpSlot = $validated['pick_up_time_slot_id'];

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
        try {
            \DB::statement('CALL AddReservation(?, ?, ?, ?, ?, ?, ?, ?)', [
                $validated['drop_off_time_slot_id'],
                $validated['pick_up_time_slot_id'],
                $validated['reservation_date'],
                $validated['user_name'],
                $validated['country'],
                $validated['license_plate'],
                $validated['vehicle_type_id'],
                $validated['email']
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reservation created successfully'
        ], 201);
    }

    // Rezervacija iz frontenda (možeš koristiti samo store, nema potrebe za duplikatom!)
    public function reserve(Request $request)
    {
        return $this->store($request);
    }

    // Slanje računa i potvrde korisniku na email (ručno)
    public function sendInvoiceToUser($id)
    {
        $reservation = Reservation::findOrFail($id);

        if (!$reservation->email) {
            return response()->json(['error' => 'Email adresa nije pronađena za ovu rezervaciju.'], 422);
        }

        $invoicePdf = $this->generateInvoicePdf($reservation);
        $confirmationPdf = $this->generateConfirmationPdf($reservation);

        Mail::to($reservation->email)->send(
            new PaymentReservationConfirmationMail(
                $reservation->user_name,
                $invoicePdf,
                $confirmationPdf
            )
        );

        return response()->json(['success' => 'Invoice and payment confirmation sent to user email.']);
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
            'status'                => 'sometimes|required|string|in:pending,paid',
        ]);

        $reservation->update($validated);

        // Šalji mail kad je status promijenjen u 'paid'
        if (
            isset($validated['status']) &&
            $validated['status'] === 'paid' &&
            $reservation->email
        ) {
            $userName = $reservation->user_name;
            $invoicePdf = $this->generateInvoicePdf($reservation);
            $confirmationPdf = $this->generateConfirmationPdf($reservation);

            Mail::to($reservation->email)->send(
                new PaymentReservationConfirmationMail($userName, $invoicePdf, $confirmationPdf)
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

    protected function generateInvoicePdf($reservation)
    {
        return Pdf::loadView('reports.reservation_invoice_pdf', [
            'reservation' => $reservation
        ])->output();
    }

    protected function generateConfirmationPdf($reservation)
    {
        return Pdf::loadView('reports.reservation_confirmation_pdf', [
            'reservation' => $reservation
        ])->output();
    }
}