<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\TempData;
<<<<<<< HEAD
use App\Models\Reservation;
use App\Models\VehicleType;
use Illuminate\Support\Facades\Http;
=======
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

class PaymentController extends Controller
{
    public function redirectToHpp(Request $request)
    {
<<<<<<< HEAD
        \Log::info('Procesiraj placanje debug', [
            'session_id' => session()->getId(),
            'session_data' => session()->all(),
            'cookies' => $request->cookies->all(),
            'headers' => $request->headers->all(),
            '_token' => $request->input('_token'),
        ]);
        
=======

>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
        $merchantTransactionId = $request->input('merchantTransactionId');
        if (!$merchantTransactionId) {
            return response()->json(['error' => 'Nedostaje merchantTransactionId.'], 400);
        }

        $temp = TempData::where('merchant_transaction_id', $merchantTransactionId)->first();
        if (!$temp) {
            return response()->json(['error' => 'Privremeni podaci nisu pronađeni.'], 404);
        }

<<<<<<< HEAD
        $amount = VehicleType::find($temp->vehicle_type_id)?->price ?? null;
=======
        $amount = \App\Models\VehicleType::find($temp->vehicle_type_id)?->price ?? null;
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
        if (!$amount) {
            return response()->json(['error' => 'Nije pronađena cena za tip vozila.'], 400);
        }

        $payload = [
            'merchantTransactionId' => $temp->merchant_transaction_id,
            'amount'                => number_format($amount, 2, '.', ''),
            'currency'              => 'EUR',
            'successUrl'            => route('payment.success', [], true),
            'errorUrl'              => route('payment.error', [], true),
            'cancelUrl'             => route('payment.cancel', [], true),
<<<<<<< HEAD
            'callbackUrl'           => route('api.payment.callback', [], true),
=======
            'callbackUrl'           => route('payment.callback', [], true),
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
            'customer' => [
                'billingAddress1' => 'Test street 1',
                'billingCity'     => 'Kotor',
                'billingCountry'  => 'ME',
                'billingPostcode' => '85330',
<<<<<<< HEAD
                'email'           => $temp->email,
            ],
        ];

        Log::info('Payment payload being sent to Bankart', [
            'callbackUrl' => $payload['callbackUrl'],
            'successUrl' => $payload['successUrl'],
            'errorUrl' => $payload['errorUrl'],
            'cancelUrl' => $payload['cancelUrl'],
        ]);

=======
                'email'           => 'test@example.com',
            ],
        ];

>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
        $apiKey    = config('services.bankart.api_key');
        $username  = config('services.bankart.username');
        $password  = config('services.bankart.password');
        $apiUrl    = rtrim(config('services.bankart.api_url'), '/') . "/transaction/{$apiKey}/debit";

        $contentType = 'application/json; charset=utf-8';

        $headers = [
            'Content-Type' => $contentType,
            'Accept'       => 'application/json',
        ];

<<<<<<< HEAD
        $bodyRaw = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (config('services.bankart.signature_enabled', false)) {
            $sharedSecret = config('services.bankart.shared_secret');
=======
        if (config('services.bankart.signature_enabled', false)) {
            $sharedSecret = config('services.bankart.shared_secret');
            $bodyRaw = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
            $date = gmdate('D, d M Y H:i:s') . ' GMT';
            $requestUri = "/api/v3/transaction/{$apiKey}/debit";
            $bodyHash = hash('sha512', $bodyRaw);
            $message = "POST\n{$bodyHash}\n{$contentType}\n{$date}\n{$requestUri}";
            $signature = base64_encode(hash_hmac('sha512', $message, $sharedSecret, true));
            $headers['X-Signature'] = $signature;
            $headers['Date'] = $date;
        }

        $client = new Client();

        try {
            $response = $client->request('POST', $apiUrl, [
                'headers' => $headers,
                'auth'    => [$username, $password],
                'body'    => $bodyRaw,
            ]);
            $responseBody = $response->getBody()->getContents();
            $data = json_decode($responseBody, true);

            Log::info('Bankart API response', ['body' => $responseBody, 'data' => $data]);

            if (isset($data['redirectUrl'])) {
                return response()->json(['redirectUrl' => $data['redirectUrl']]);
            }

            Log::error('Bankart init error', [
                'request_payload'   => $payload,
                'bankart_response'  => $data
            ]);
            return back()->with('error', $data['message'] ?? 'Greška pri inicijalizaciji plaćanja.');
        } catch (BadResponseException $e) {
            $errorMsg = $e->getMessage();
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
            Log::error('Bankart API Exception', [
                'request_payload' => $payload,
                'exception' => $errorMsg,
                'response'  => $errorBody,
            ]);
            return back()->with('error', 'Greška pri komunikaciji s bankom. ' . $errorMsg);
        }
    }

    /**
<<<<<<< HEAD
     * Bankart callback - ovdje se radi fiskalizacija na backendu!
=======
     * Callback endpoint koji Bankart poziva nakon transakcije.
     * Ovde NIŠTA ne radiš direktno sa reservations!
     * Pozivaš ReservationController@storeFromTemp!
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
     */
    public function callback(Request $request)
    {
        $uniqueId = uniqid();
        Log::info('Bankart callback method called', [
            'unique_id' => $uniqueId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'content_type' => $request->header('Content-Type'),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString(),
            'session_id' => session()->getId()
        ]);

        // Očisti stare temp podatke (npr. starije od 24h)
        \App\Models\TempData::where('created_at', '<', now()->subHours(24))->delete();

        Log::info('Bankart callback method called', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $request->headers->all(),
            'content_type' => $request->header('Content-Type'),
            'user_agent' => $request->header('User-Agent'),
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString(),
            'session_id' => session()->getId()
        ]);

        $payload = $request->getContent();
        $headers = $request->headers;
        $sharedSecret = config('services.bankart.shared_secret');
        $signature = $headers->get('x-signature') ?? $headers->get('X-Signature');

        Log::info('Bankart callback received', [
            'headers' => $headers->all(),
<<<<<<< HEAD
            'payload' => $payload,
            'payload_length' => strlen($payload)
        ]);

        if (config('services.bankart.signature_enabled', false)) {
            $contentType = 'application/json; charset=utf-8';
            $date = $headers->get('x-date') ?? $headers->get('date');
            $uri = $request->getRequestUri();
=======
            'payload' => $payload
        ]);

        if (config('services.bankart.signature_enabled', false)) {
            // Sklapanje signature kao za outgoing!
            $contentType = 'application/json; charset=utf-8';
            $date = $headers->get('x-date') ?? $headers->get('date');
            $uri = $request->getRequestUri(); // Ovo uključuje path i eventualni query string
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
            $bodyHash = hash('sha512', $payload);
            $message = "POST\n{$bodyHash}\n{$contentType}\n{$date}\n{$uri}";
            $expectedSignature = base64_encode(hash_hmac('sha512', $message, $sharedSecret, true));

            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning('Bankart: Invalid callback signature!', [
                    'signature' => $signature,
                    'expected'  => $expectedSignature,
                    'payload'   => $payload,
                    'message'   => $message,
                ]);
                abort(403, 'Invalid signature');
            }
        }

        $data = json_decode($payload, true);

<<<<<<< HEAD
        // Prilagođeno za Bankart - oni šalju result: "OK" umjesto status: "PAID"
        if (isset($data['result']) && $data['result'] === 'OK' && !empty($data['merchantTransactionId'])) {
            $merchantTransactionId = $data['merchantTransactionId'];
            
            // Proveri da li se ovaj merchantTransactionId već obrađuje
            $lockKey = 'bankart_callback_' . $merchantTransactionId;
            $lockAcquired = \Cache::add($lockKey, $uniqueId, 30); // 30 sekundi lock
            
            if (!$lockAcquired) {
                Log::info('Bankart callback - već se obrađuje', [
                    'unique_id' => $uniqueId,
                    'merchantTransactionId' => $merchantTransactionId,
                    'lock_key' => $lockKey
                ]);
                return response('OK', 200)->header('Content-Type', 'text/plain');
            }
            
            Log::info('Bankart callback - lock uspešno stečen', [
                'unique_id' => $uniqueId,
                'merchantTransactionId' => $merchantTransactionId,
                'lock_key' => $lockKey
            ]);
            
            Log::info('Bankart callback - processing successful payment', [
                'merchantTransactionId' => $merchantTransactionId,
                'result' => $data['result'],
                'timestamp' => now()->toISOString()
            ]);
            
            $temp = \App\Models\TempData::where('merchant_transaction_id', $merchantTransactionId)->first();
            if (!$temp) {
                Log::error('TempData not found for merchantTransactionId: ' . $merchantTransactionId);
                return response('OK', 200)
                    ->header('Content-Type', 'text/plain');
            }

            // Proveri da li je rezervacija već kreirana
            $existingReservation = \App\Models\Reservation::where('merchant_transaction_id', $merchantTransactionId)->first();
            if ($existingReservation) {
                Log::info('Bankart callback - reservation already exists, skipping processing', [
                    'merchantTransactionId' => $merchantTransactionId,
                    'reservation_id' => $existingReservation->id,
                    'status' => $existingReservation->status
                ]);
                return response('OK', 200)
                    ->header('Content-Type', 'text/plain');
            }

            // 1. Fiskalizacija
            $fiskalController = new \App\Http\Controllers\FiskalController();
            
            // Initialize deposit
            $initDepositResult = $fiskalController->initDeposit($merchantTransactionId);
            if (!$initDepositResult['success']) {
                Log::error('Fiskal initDeposit failed', [
                    'merchantTransactionId' => $merchantTransactionId,
                    'error' => $initDepositResult['error']
                ]);
                // Continue with payment even if deposit fails
            }

            // Perform fiscalization
            $fiscalizationResult = $fiskalController->fiscalization($merchantTransactionId);
            $fiscalizationSuccess = false;

            if (!$fiscalizationResult['success']) {
                Log::error('Fiskal fiscalization failed', [
                    'merchantTransactionId' => $merchantTransactionId,
                    'error' => $fiscalizationResult['error']
                ]);
                // Continue with payment even if fiscalization fails
            } else {
                Log::info('Fiskalizacija uspješna', [
                    'merchantTransactionId' => $merchantTransactionId,
                    'documentNumber' => $fiscalizationResult['documentNumber'] ?? null,
                    'data' => $fiscalizationResult['data'] ?? null
                ]);
                $fiscalizationSuccess = true;
            }

            // 2. Upis u reservations preko storeFromTemp (email se šalje unutar storeFromTemp)
            $reservationController = new ReservationController(app(\App\Services\SlotService::class));
            $storeRequest = new Request([
                'merchant_transaction_id' => $merchantTransactionId,
                'fiscalization_success' => $fiscalizationSuccess
            ]);
            
            Log::info('Bankart callback - calling storeFromTemp', [
                'merchantTransactionId' => $merchantTransactionId,
                'fiscalization_success' => $fiscalizationSuccess
            ]);
            
            $storeResponse = $reservationController->storeFromTemp($storeRequest);

            Log::info('Bankart callback - storeFromTemp completed', [
                'merchantTransactionId' => $merchantTransactionId,
                'response_status' => $storeResponse->getStatusCode()
            ]);

            // Pošalji email nakon uspešnog kreiranja rezervacije
            if ($storeResponse->getStatusCode() === 200) {
                $reservation = \App\Models\Reservation::where('merchant_transaction_id', $merchantTransactionId)->first();
                if ($reservation && $reservation->email) {
                    Log::info('Bankart callback - šaljem email', [
                        'email' => $reservation->email,
                        'reservation_id' => $reservation->id,
                        'email_sent_exists' => \Schema::hasColumn('reservations', 'email_sent'),
                        'current_email_sent_value' => $reservation->email_sent ?? 'null'
                    ]);
                    
                    try {
                        $invoicePdf = $reservationController->generateInvoicePdf($reservation);

                        // Proveri da li postoji email_sent kolona
                        if (\Schema::hasColumn('reservations', 'email_sent')) {
                            // ATOMIČNA PROVERA: Proveri i ažuriraj email_sent u jednoj SQL komandi
                            $rowsAffected = \DB::table('reservations')
                                ->where('id', $reservation->id)
                                ->where('email_sent', 0) // Samo ako nije poslat
                                ->update(['email_sent' => 1]);
                            
                            if ($rowsAffected > 0) {
                                // Mail nije bio poslat, sada ga pošalji
                                \Mail::to($reservation->email)->send(
                                    new \App\Mail\PaymentReservationConfirmationMail(
                                        $reservation->user_name,
                                        $invoicePdf,
                                        null // Treći argument za kompatibilnost sa serverom
                                    )
                                );
                                
                                Log::info('Bankart callback - email poslat (atomična provera)', [
                                    'reservation_id' => $reservation->id,
                                    'rows_affected' => $rowsAffected
                                ]);
                            } else {
                                // Mail je već poslat u drugom pozivu
                                Log::info('Bankart callback - email već poslat u drugom pozivu', [
                                    'reservation_id' => $reservation->id,
                                    'rows_affected' => $rowsAffected
                                ]);
                            }
                        } else {
                            // Ako kolona ne postoji, pošalji email bez provere
                            \Mail::to($reservation->email)->send(
                                new \App\Mail\PaymentReservationConfirmationMail(
                                    $reservation->user_name,
                                    $invoicePdf,
                                    null // Treći argument za kompatibilnost sa serverom
                                )
                            );
                            Log::info('Bankart callback - email poslat (email_sent kolona ne postoji)', ['reservation_id' => $reservation->id]);
                        }


                    } catch (\Exception $e) {
                        Log::error('Bankart callback - greška pri slanju email-a', [
                            'error' => $e->getMessage(),
                            'email' => $reservation->email,
                            'reservation_id' => $reservation->id
                        ]);
                        // Ne vraćaj grešku jer je rezervacija već kreirana
                    }
                }
            }

            Log::info('Bankart callback method completed - success path', [
                'unique_id' => $uniqueId,
                'timestamp' => now()->toISOString()
            ]);
            
            // Oslobodi lock
            if ($merchantTransactionId) {
                $lockKey = 'bankart_callback_' . $merchantTransactionId;
                \Cache::forget($lockKey);
                Log::info('Bankart callback - lock oslobođen', [
                    'unique_id' => $uniqueId,
                    'merchantTransactionId' => $merchantTransactionId,
                    'lock_key' => $lockKey
                ]);
            }
            
            return $storeResponse;
        } else {
            Log::warning('Bankart callback: payment not successful or missing data', [
                'unique_id' => $uniqueId,
                'result' => $data['result'] ?? 'missing',
                'merchantTransactionId' => $data['merchantTransactionId'] ?? 'missing',
                'data' => $data
            ]);
            Log::info('Bankart callback method completed - failure path', [
                'unique_id' => $uniqueId,
                'timestamp' => now()->toISOString()
            ]);
            
            // Oslobodi lock
            if ($merchantTransactionId) {
                $lockKey = 'bankart_callback_' . $merchantTransactionId;
                \Cache::forget($lockKey);
                Log::info('Bankart callback - lock oslobođen (failure path)', [
                    'unique_id' => $uniqueId,
                    'merchantTransactionId' => $merchantTransactionId,
                    'lock_key' => $lockKey
                ]);
            }
            
            return response('OK', 200)
                ->header('Content-Type', 'text/plain');
        }
    }

    public function success(Request $request)
    {
        \Log::info('Payment success page called', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'query_params' => $request->all(),
            'session_id' => session()->getId(),
            'timestamp' => now()->toISOString()
        ]);
        
        // Prvo pokušaj iz query parametra
        $reservationId = $request->query('reservation_id');
        
        // Ako nema u query, pokušaj iz sesije
        if (!$reservationId) {
            $reservationId = session('last_reservation_id');
            \Log::info('Payment success - got reservation_id from session', ['reservation_id' => $reservationId]);
        } else {
            \Log::info('Payment success - got reservation_id from query', ['reservation_id' => $reservationId]);
        }
        
        // Ako i dalje nema, pokušaj da nađeš poslednju rezervaciju za ovog korisnika
        if (!$reservationId) {
            $reservation = \App\Models\Reservation::where('status', 'paid')
                ->orderBy('created_at', 'desc')
                ->first();
            $reservationId = $reservation ? $reservation->id : null;
            \Log::info('Payment success - got reservation_id from latest paid reservation', ['reservation_id' => $reservationId]);
        }
        
        \Log::info('Payment success page final', [
            'reservation_id' => $reservationId,
            'query_params' => $request->all(),
            'session_data' => session()->all()
        ]);
        
        return view('payment.success', compact('reservationId'));
=======
        if (isset($data['status']) && $data['status'] === 'PAID' && !empty($data['merchantTransactionId'])) {
            $reservationController = app(\App\Http\Controllers\ReservationController::class);
            $subRequest = new Request(['merchant_transaction_id' => $data['merchantTransactionId']]);
            $response = $reservationController->storeFromTemp($subRequest);

            Log::info('Pozvana storeFromTemp nakon PAID', [
                'merchantTransactionId' => $data['merchantTransactionId'],
                'result' => $response->getContent()
            ]);
        } else {
            Log::warning('Bankart callback: missing or unexpected status', [
                'data' => $data
            ]);
            return response()->json(['status' => 'ok'], 200);
        }
        // Kraj funkcije callback -- ova zagrada je jako bitna!
    }

    public function success()
    {
        return view('payment.success');
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
    }

    public function cancel()
    {
        return view('payment.cancel');
    }

    public function error()
    {
        return view('payment.error');
    }
}