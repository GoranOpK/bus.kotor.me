<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\TempData;
use App\Models\Reservation;
use App\Models\VehicleType;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

class PaymentController extends Controller
{
    public function redirectToHpp(Request $request)
    {
        \Log::info('Procesiraj placanje debug', [
            'session_id' => session()->getId(),
            'session_data' => session()->all(),
            'cookies' => $request->cookies->all(),
            'headers' => $request->headers->all(),
            '_token' => $request->input('_token'),
        ]);
        
        $merchantTransactionId = $request->input('merchantTransactionId');
        if (!$merchantTransactionId) {
            return response()->json(['error' => 'Nedostaje merchantTransactionId.'], 400);
        }

        $temp = TempData::where('merchant_transaction_id', $merchantTransactionId)->first();
        if (!$temp) {
            return response()->json(['error' => 'Privremeni podaci nisu pronađeni.'], 404);
        }

        $amount = VehicleType::find($temp->vehicle_type_id)?->price ?? null;
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
            'callbackUrl'           => route('api.payment.callback', [], true),
            'customer' => [
                'billingAddress1' => 'Test street 1',
                'billingCity'     => 'Kotor',
                'billingCountry'  => 'ME',
                'billingPostcode' => '85330',
                'email'           => $temp->email,
            ],
        ];

        Log::info('Payment payload being sent to Bankart', [
            'callbackUrl' => $payload['callbackUrl'],
            'successUrl' => $payload['successUrl'],
            'errorUrl' => $payload['errorUrl'],
            'cancelUrl' => $payload['cancelUrl'],
        ]);

        $apiKey    = config('services.bankart.api_key');
        $username  = config('services.bankart.username');
        $password  = config('services.bankart.password');
        $apiUrl    = rtrim(config('services.bankart.api_url'), '/') . "/transaction/{$apiKey}/debit";

        $contentType = 'application/json; charset=utf-8';

        $headers = [
            'Content-Type' => $contentType,
            'Accept'       => 'application/json',
        ];

        $bodyRaw = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (config('services.bankart.signature_enabled', false)) {
            $sharedSecret = config('services.bankart.shared_secret');
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
     * Bankart callback - ovdje se radi fiskalizacija na backendu!
     * 
     * VAŽNO: Ova metoda sadrži lock mehanizam da spreči duplo obrađivanje
     * istog merchantTransactionId. Takođe sadrži validaciju da se osigura
     * da se email šalje pravoj rezervaciji.
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
        // \App\Models\TempData::where('created_at', '<', now()->subHours(24))->delete();

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
            'payload' => $payload,
            'payload_length' => strlen($payload)
        ]);

        if (config('services.bankart.signature_enabled', false)) {
            $contentType = 'application/json; charset=utf-8';
            $date = $headers->get('x-date') ?? $headers->get('date');
            $uri = $request->getRequestUri();
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

        // Prilagođeno za Bankart - oni šalju result: "OK" umjesto status: "PAID"
        if (isset($data['result']) && $data['result'] === 'OK' && !empty($data['merchantTransactionId'])) {
            $merchantTransactionId = $data['merchantTransactionId'];
            
            // PRVA PROVERA: Da li rezervacija već postoji u bazi
            $existingReservation = \App\Models\Reservation::where('merchant_transaction_id', $merchantTransactionId)->first();
            if ($existingReservation) {
                Log::info('Bankart callback - reservation already exists in database, returning OK', [
                    'unique_id' => $uniqueId,
                    'merchantTransactionId' => $merchantTransactionId,
                    'reservation_id' => $existingReservation->id,
                    'status' => $existingReservation->status
                ]);
                return response('OK', 200)->header('Content-Type', 'text/plain');
            }
            
            // DRUGA PROVERA: Da li se ovaj merchantTransactionId već obrađuje
            $lockKey = 'bankart_callback_' . $merchantTransactionId;
            $lockAcquired = \Cache::add($lockKey, $uniqueId, 60); // Povećaj na 60 sekundi lock
            
            if (!$lockAcquired) {
                Log::info('Bankart callback - već se obrađuje, proveravam da li je rezervacija kreirana', [
                    'unique_id' => $uniqueId,
                    'merchantTransactionId' => $merchantTransactionId,
                    'lock_key' => $lockKey
                ]);
                
                // Proveri da li je rezervacija kreirana u međuvremenu
                $existingReservation = \App\Models\Reservation::where('merchant_transaction_id', $merchantTransactionId)->first();
                if ($existingReservation) {
                    Log::info('Bankart callback - reservation created by another process, returning OK', [
                        'unique_id' => $uniqueId,
                        'merchantTransactionId' => $merchantTransactionId,
                        'reservation_id' => $existingReservation->id
                    ]);
                    return response('OK', 200)->header('Content-Type', 'text/plain');
                }
                
                // Ako nema rezervacije, vrati OK da ne blokiramo callback
                Log::warning('Bankart callback - lock acquired by another process but no reservation found, returning OK', [
                    'unique_id' => $uniqueId,
                    'merchantTransactionId' => $merchantTransactionId
                ]);
                return response('OK', 200)->header('Content-Type', 'text/plain');
            }
            
            Log::info('Bankart callback - lock uspešno stečen', [
                'unique_id' => $uniqueId,
                'merchantTransactionId' => $merchantTransactionId,
                'lock_key' => $lockKey
            ]);
            
            try {
                Log::info('Bankart callback - processing successful payment', [
                    'merchantTransactionId' => $merchantTransactionId,
                    'result' => $data['result'],
                    'timestamp' => now()->toISOString()
                ]);
                
                // DODATNA PROVERA: Da li je rezervacija kreirana u međuvremenu
                $existingReservation = \App\Models\Reservation::where('merchant_transaction_id', $merchantTransactionId)->first();
                if ($existingReservation) {
                    Log::info('Bankart callback - reservation created by another process during processing, returning OK', [
                        'unique_id' => $uniqueId,
                        'merchantTransactionId' => $merchantTransactionId,
                        'reservation_id' => $existingReservation->id
                    ]);
                    return response('OK', 200)->header('Content-Type', 'text/plain');
                }
                
                $temp = \App\Models\TempData::where('merchant_transaction_id', $merchantTransactionId)->first();
                if (!$temp) {
                    Log::error('TempData not found for merchantTransactionId: ' . $merchantTransactionId);
                    
                    // Provjeri da li je rezervacija možda već kreirana ranije
                    $existingReservation2 = \App\Models\Reservation::where('merchant_transaction_id', $merchantTransactionId)->first();
                    if ($existingReservation2) {
                        Log::info('Bankart callback - rezervacija već postoji, temp podaci obrisani', [
                            'merchantTransactionId' => $merchantTransactionId,
                            'reservation_id' => $existingReservation2->id,
                            'status' => $existingReservation2->status
                        ]);
                        return response('OK', 200)->header('Content-Type', 'text/plain');
                    }
                    
                    // Ako nema ni temp podataka ni rezervacije, ovo je problem
                    Log::error('KRITIČNA GREŠKA: Uspješno plaćanje bez temp podataka ili rezervacije', [
                        'merchantTransactionId' => $merchantTransactionId,
                        'payment_data' => $data
                    ]);
                    
                    // Kreiraj admin notifikaciju ili pošalji email adminu
                    try {
                        \Mail::raw("KRITIČNA GREŠKA: Uspješno plaćanje {$merchantTransactionId} bez temp podataka ili rezervacije!\n\nPayment data:\n" . json_encode($data, JSON_PRETTY_PRINT), function ($message) {
                            $message->to('bus@kotor.me')
                                ->subject('KRITIČNA GREŠKA: Plaćanje bez rezervacije');
                        });
                    } catch (\Exception $mailException) {
                        Log::error('Greška pri slanju notifikacije adminu', ['error' => $mailException->getMessage()]);
                    }
                    
                    return response('OK', 200)->header('Content-Type', 'text/plain');
                }

                // DODATNA PROVERA: Da li su temp podaci stariji od 1 sata (možda su "zaglavljeni")
                $oneHourAgo = now()->subHour();
                if ($temp->created_at < $oneHourAgo) {
                    Log::warning('Bankart callback - temp podaci stariji od 1 sata, možda je race condition', [
                        'merchantTransactionId' => $merchantTransactionId,
                        'temp_created_at' => $temp->created_at,
                        'current_time' => now(),
                        'age_hours' => $temp->created_at->diffInHours(now())
                    ]);
                    
                    // Ažuriraj status da označi da je problematičan
                    $temp->update([
                        'status' => 'paid_slots_full',
                        'updated_at' => now()
                    ]);
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

                // Ako storeFromTemp nije uspešan, vrati grešku banci
                if ($storeResponse->getStatusCode() !== 200) {
                    Log::error('Bankart callback - storeFromTemp failed', [
                        'merchantTransactionId' => $merchantTransactionId,
                        'response_status' => $storeResponse->getStatusCode(),
                        'response_content' => $storeResponse->getContent()
                    ]);
                    Log::error('Bankart callback - returning error response to bank', [
                    'merchantTransactionId' => $merchantTransactionId,
                    'response_status' => 500
                ]);
                return response('ERROR', 500)->header('Content-Type', 'text/plain');
                }

                // Pošalji email nakon uspešnog kreiranja rezervacije
                if ($storeResponse->getStatusCode() === 200) {
                    $reservation = \App\Models\Reservation::where('merchant_transaction_id', $merchantTransactionId)->first();
                    if ($reservation && $reservation->email) {
                        Log::info('Bankart callback - šaljem email', [
                            'email' => $reservation->email,
                            'reservation_id' => $reservation->id,
                            'user_name' => $reservation->user_name,
                            'license_plate' => $reservation->license_plate,
                            'email_sent_exists' => \Schema::hasColumn('reservations', 'email_sent'),
                            'current_email_sent_value' => $reservation->email_sent ?? 'null'
                        ]);
                        
                        try {
                            // DODATNA VALIDACIJA: Proveri da li su podaci rezervacije validni
                            if (empty($reservation->email) || empty($reservation->user_name) || empty($reservation->license_plate)) {
                                Log::error('Bankart callback - rezervacija ima nepotpune podatke', [
                                    'reservation_id' => $reservation->id,
                                    'email' => $reservation->email,
                                    'user_name' => $reservation->user_name,
                                    'license_plate' => $reservation->license_plate
                                ]);
                                // Ne šalji email za nepotpune podatke, ali označi kao obrađeno
                                return response('OK', 200)->header('Content-Type', 'text/plain');
                            }

                            // Generisanje PDF-a sa error handling
                            try {
                                // Postavi default jezik pošto smo odustali od višejezičnosti
                                $invoicePdf = $reservationController->generateInvoicePdf($reservation, 'en');
                            } catch (\Exception $e) {
                                Log::error('Bankart callback - greška pri generisanju PDF-a', [
                                    'error' => $e->getMessage(),
                                    'reservation_id' => $reservation->id
                                ]);
                                // Ne šalji email za greške u PDF generisanju
                                return response('OK', 200)->header('Content-Type', 'text/plain');
                            }

                            // Proveri da li postoji email_sent kolona
                            if (\Schema::hasColumn('reservations', 'email_sent')) {
                                // ATOMIČNA PROVERA: Proveri i ažuriraj email_sent u jednoj SQL komandi
                                // Koristi IS NULL OR email_sent = 0 da pokrije i NULL vrednosti
                                $rowsAffected = \DB::table('reservations')
                                    ->where('id', $reservation->id)
                                    ->where(function($query) {
                                        $query->whereNull('email_sent')
                                              ->orWhere('email_sent', 0);
                                    })
                                    ->update(['email_sent' => 1]);
                                
                                if ($rowsAffected > 0) {
                                    // Mail nije bio poslat, sada ga pošalji
                                    \Mail::to($reservation->email)->send(
                                        new \App\Mail\PaymentReservationConfirmationMail(
                                            $reservation->user_name,
                                            $invoicePdf,
                                            null, // Treći argument za kompatibilnost sa serverom
                                            false, // Četvrti argument - nije besplatna rezervacija
                                            'en' // Peti argument - default jezik
                                        )
                                    );
                                    
                                    Log::info('Bankart callback - email poslat (atomična provera)', [
                                        'reservation_id' => $reservation->id,
                                        'rows_affected' => $rowsAffected,
                                        'email' => $reservation->email,
                                        'user_name' => $reservation->user_name
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
                                        null, // Treći argument za kompatibilnost sa serverom
                                        false, // Četvrti argument - nije besplatna rezervacija
                                        'en' // Peti argument - default jezik
                                    )
                                );
                                Log::info('Bankart callback - email poslat (email_sent kolona ne postoji)', [
                                    'reservation_id' => $reservation->id,
                                    'email' => $reservation->email,
                                    'user_name' => $reservation->user_name
                                ]);
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


                // SIGURNOST: Postavi merchant_transaction_id u sesiju za autorizaciju download-a
                if ($reservation && $reservation->merchant_transaction_id) {
                    session([
                        'last_merchant_transaction_id' => $reservation->merchant_transaction_id,
                        'user_email' => $reservation->email
                    ]);
                    Log::info('Bankart callback - postavljen merchant_transaction_id i email u sesiju', [
                        'reservation_id' => $reservation->id,
                        'merchant_transaction_id' => $reservation->merchant_transaction_id,
                        'user_email' => $reservation->email
                    ]);
                }
                
                Log::info('Bankart callback method completed - success path', [
                    'unique_id' => $uniqueId,
                    'timestamp' => now()->toISOString()
                ]);
                
                // Oslobodi lock
                if (isset($merchantTransactionId) && $merchantTransactionId) {
                    $lockKey = 'bankart_callback_' . $merchantTransactionId;
                    \Cache::forget($lockKey);
                    Log::info('Bankart callback - lock oslobođen', [
                        'unique_id' => $uniqueId,
                        'merchantTransactionId' => $merchantTransactionId,
                        'lock_key' => $lockKey
                    ]);
                }
                
                Log::info('Bankart callback - returning success response to bank', [
                    'merchantTransactionId' => $merchantTransactionId,
                    'response_status' => 200
                ]);
                
                return $storeResponse;
            } catch (\Exception $e) {
                Log::error('Bankart callback - greška u glavnoj logici', [
                    'error' => $e->getMessage(),
                    'unique_id' => $uniqueId,
                    'merchantTransactionId' => $merchantTransactionId
                ]);
                // Oslobodi lock u slučaju greške
                if (isset($merchantTransactionId) && $merchantTransactionId) {
                    $lockKey = 'bankart_callback_' . $merchantTransactionId;
                    \Cache::forget($lockKey);
                    Log::info('Bankart callback - lock oslobođen (greška u glavnoj logici)', [
                        'unique_id' => $uniqueId,
                        'merchantTransactionId' => $merchantTransactionId,
                        'lock_key' => $lockKey
                    ]);
                }
                return response('OK', 200)
                    ->header('Content-Type', 'text/plain');
            }
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
            if (isset($merchantTransactionId) && $merchantTransactionId) {
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
        
        $reservationId = null;
        $merchantTransactionId = null;
        
        // PRIORITET 1: merchant_transaction_id iz sesije (postavljen u callback-u)
        $merchantTxId = session('last_merchant_transaction_id');
        if ($merchantTxId) {
            // Proveri da li rezervacija već postoji
            $reservation = \App\Models\Reservation::where('merchant_transaction_id', $merchantTxId)->first();
            if ($reservation) {
                $reservationId = $reservation->id;
                $merchantTransactionId = $merchantTxId;
                \Log::info('Payment success - rezervacija pronađena odmah', [
                    'merchant_transaction_id' => $merchantTxId,
                    'reservation_id' => $reservationId
                ]);
            } else {
                // Ako rezervacija ne postoji, proveri da li postoji temp_data
                $temp = \App\Models\TempData::where('merchant_transaction_id', $merchantTxId)
                    ->whereIn('status', ['available', 'reserved', 'failed'])
                    ->first();
                
                if ($temp) {
                    \Log::info('Payment success - temp_data postoji, callback možda još uvek u toku', [
                        'merchant_transaction_id' => $merchantTxId,
                        'temp_id' => $temp->id,
                        'temp_status' => $temp->status
                    ]);
                    
                    // Prikaži poruku korisniku da sačeka
                    return view('payment.success', [
                        'reservationId' => null,
                        'merchantTransactionId' => $merchantTxId,
                        'waitingForReservation' => true,
                        'message' => 'Vaša uplata je primljena. Molimo sačekajte da se rezervacija završi...'
                    ]);
                } else {
                    // Ako nema ni temp_data, nešto je pogrešno
                    return redirect('/')->with('error', 'Greška pri kreiranju rezervacije. Molimo kontaktirajte podršku.');
                }
            }
        }
        
        // PRIORITET 2: reservation_id iz sesije (fallback)
        if (!$reservationId) {
            $reservationId = session('last_reservation_id');
            if ($reservationId) {
                \Log::info('Payment success - got reservation_id from session', ['reservation_id' => $reservationId]);
            }
        }
        
        // PRIORITET 2: reservation_id iz sesije (fallback)
        if (!$reservationId) {
            $reservationId = session('last_reservation_id');
            if ($reservationId) {
                \Log::info('Payment success - got reservation_id from session', ['reservation_id' => $reservationId]);
            }
        }
        
        // PRIORITET 3: reservation_id iz query parametra (fallback)
        if (!$reservationId) {
            $reservationId = $request->query('reservation_id');
            if ($reservationId) {
                \Log::info('Payment success - got reservation_id from query param', ['reservation_id' => $reservationId]);
            }
        }
        
        // PRIORITET 4: merchant_transaction_id iz query parametra (za testiranje)
        if (!$reservationId) {
            $merchantTxIdFromQuery = $request->query('merchant_transaction_id');
            if ($merchantTxIdFromQuery) {
                $reservation = \App\Models\Reservation::where('merchant_transaction_id', $merchantTxIdFromQuery)
                    ->where('created_at', '>=', now()->subMinutes(10))
                    ->first();
                if ($reservation) {
                    $reservationId = $reservation->id;
                    $merchantTransactionId = $merchantTxIdFromQuery;
                    \Log::info('Payment success - got reservation_id from merchant_transaction_id query param', [
                        'merchant_transaction_id' => $merchantTxIdFromQuery,
                        'reservation_id' => $reservationId
                    ]);
                }
            }
        }
        
        // Ako i dalje nema, pokušaj da nađeš najnoviju rezervaciju za ovog korisnika
        if (!$reservationId) {
            // SIGURNOST: Koristi temp_data za pronalaženje rezervacije
            $sessionMerchantTxId = session('last_merchant_transaction_id');
            if ($sessionMerchantTxId) {
                // Proveri da li postoji temp_data za ovaj merchant_transaction_id (samo neobrađeni)
                $temp = \App\Models\TempData::where('merchant_transaction_id', $sessionMerchantTxId)
                    ->whereIn('status', ['available', 'reserved', 'failed'])
                    ->first();
                if ($temp) {
                    // Pokušaj da nađeš rezervaciju koja je kreirana u poslednjih 2 minuta
                    // sa istim podacima kao temp_data (sigurniji pristup)
                    $recentReservation = \App\Models\Reservation::where([
                        ['email', $temp->email],
                        ['license_plate', $temp->license_plate],
                        ['reservation_date', $temp->reservation_date],
                        ['drop_off_time_slot_id', $temp->drop_off_time_slot_id],
                        ['pick_up_time_slot_id', $temp->pick_up_time_slot_id],
                        ['created_at', '>=', now()->subMinutes(2)]
                    ])->orderBy('created_at', 'desc')->first();
                    
                    if ($recentReservation) {
                        $reservationId = $recentReservation->id;
                        $merchantTransactionId = $recentReservation->merchant_transaction_id;
                        \Log::info('Payment success - found recent reservation by temp_data match', [
                            'merchant_transaction_id' => $sessionMerchantTxId,
                            'reservation_id' => $reservationId,
                            'temp_id' => $temp->id
                        ]);
                    }
                }
            }
            
            // DODATNA PROVERA: Ako nema temp_data (možda je obrisan), pokušaj da nađeš
            // rezervaciju po merchant_transaction_id direktno
            if (!$reservationId && $sessionMerchantTxId) {
                $directReservation = \App\Models\Reservation::where('merchant_transaction_id', $sessionMerchantTxId)
                    ->where('created_at', '>=', now()->subMinutes(5))
                    ->first();
                
                if ($directReservation) {
                    $reservationId = $directReservation->id;
                    $merchantTransactionId = $sessionMerchantTxId;
                    \Log::info('Payment success - found reservation directly by merchant_transaction_id', [
                        'merchant_transaction_id' => $sessionMerchantTxId,
                        'reservation_id' => $reservationId
                    ]);
                }
            }
            
            // FINALNA PROVERA: Ako i dalje nema, pokušaj da nađeš najnoviju rezervaciju
            // za ovog korisnika (bez obzira na merchant_transaction_id)
            if (!$reservationId) {
                // Pokušaj da nađeš email iz sesije ili request-a
                $userEmail = session('user_email') ?? $request->query('email');
                
                if ($userEmail) {
                    $latestReservation = \App\Models\Reservation::where('email', $userEmail)
                        ->where('created_at', '>=', now()->subMinutes(10))
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    if ($latestReservation) {
                        $reservationId = $latestReservation->id;
                        $merchantTransactionId = $latestReservation->merchant_transaction_id;
                        \Log::info('Payment success - found latest reservation by email', [
                            'email' => $userEmail,
                            'reservation_id' => $reservationId,
                            'merchant_transaction_id' => $merchantTransactionId
                        ]);
                    }
                }
                
                // ULTIMATIVNI FALLBACK: Ako i dalje nema, nađi najnoviju rezervaciju
                // kreiranu u poslednjih 5 minuta (za testiranje)
                if (!$reservationId) {
                    $latestReservation = \App\Models\Reservation::where('created_at', '>=', now()->subMinutes(5))
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    if ($latestReservation) {
                        $reservationId = $latestReservation->id;
                        $merchantTransactionId = $latestReservation->merchant_transaction_id;
                        \Log::info('Payment success - found latest reservation by creation time (fallback)', [
                            'reservation_id' => $reservationId,
                            'merchant_transaction_id' => $merchantTransactionId,
                            'email' => $latestReservation->email
                        ]);
                    }
                }
            }
        }
        
        // Ako i dalje nema rezervaciju, vrati grešku
        if (!$reservationId) {
            \Log::warning('Payment success - no reservation_id found, redirecting to home', [
                'session_data' => session()->all(),
                'query_params' => $request->all()
            ]);
            
            return redirect('/')->with('error', 'Nije moguće pronaći vašu rezervaciju. Molimo kontaktirajte podršku.');
        }
        
        // PROVERI DA LI REZERVACIJA POSTOJI U BAZI
        $reservation = \App\Models\Reservation::find($reservationId);
        if (!$reservation) {
            \Log::error('Payment success - rezervacija nije pronađena u bazi', [
                'reservation_id' => $reservationId,
                'session_data' => session()->all()
            ]);
            
            return redirect('/')->with('error', 'Rezervacija nije pronađena u bazi. Molimo kontaktirajte podršku.');
        }
        
        \Log::info('Payment success page final', [
            'reservation_id' => $reservationId,
            'merchant_transaction_id' => $merchantTransactionId,
            'query_params' => $request->all(),
            'session_data' => session()->all()
        ]);
        
        // POSTAVI SESSION PODATKE AKO NISU POSTAVLJENI
        if ($reservationId && !session('last_reservation_id')) {
            session(['last_reservation_id' => $reservationId]);
            \Log::info('Payment success - postavljen last_reservation_id u sesiju', [
                'reservation_id' => $reservationId
            ]);
        }
        
        if ($merchantTransactionId && !session('last_merchant_transaction_id')) {
            session(['last_merchant_transaction_id' => $merchantTransactionId]);
            \Log::info('Payment success - postavljen last_merchant_transaction_id u sesiju', [
                'merchant_transaction_id' => $merchantTransactionId
            ]);
        }
        
        // SIGURNOST: Proveri da li je ova rezervacija vezana za trenutnu sesiju
        if ($reservationId) {
            $reservation = \App\Models\Reservation::find($reservationId);
            if ($reservation && $reservation->merchant_transaction_id) {
                $currentSessionMerchantTxId = session('last_merchant_transaction_id');
                $isAuthorized = false;
                
                // Proveri da li se merchant_transaction_id poklapaju
                if ($currentSessionMerchantTxId && $reservation->merchant_transaction_id == $currentSessionMerchantTxId) {
                    $isAuthorized = true;
                }
                
                // Proveri da li je rezervacija kreirana u poslednjih 30 minuta (fallback)
                if (!$isAuthorized && $reservation->created_at->diffInMinutes(now()) <= 30) {
                    // DODATNA PROVERA: da li je ovo stvarno korisnikova rezervacija
                    // Proveri da li postoji last_reservation_id u sesiji koji se poklapa
                    $sessionReservationId = session('last_reservation_id');
                    if ($sessionReservationId && $sessionReservationId == $reservationId) {
                        $isAuthorized = true;
                    }
                    
                    // DODATNA PROVERA: Ako je rezervacija pronađena kroz fallback logiku
                    // i kreirana je u poslednjih 5 minuta, dozvoli pristup
                    if (!$isAuthorized && $reservation->created_at->diffInMinutes(now()) <= 5) {
                        $isAuthorized = true;
                        \Log::info('Payment success - autorizacija kroz fallback logiku', [
                            'reservation_id' => $reservationId,
                            'created_at' => $reservation->created_at,
                            'minutes_ago' => $reservation->created_at->diffInMinutes(now())
                        ]);
                    }
                    
                    // DODATNA PROVERA: Ako je rezervacija kreirana u poslednjih 2 minuta
                    // i nema merchant_transaction_id u sesiji, dozvoli pristup
                    if (!$isAuthorized && $reservation->created_at->diffInMinutes(now()) <= 2) {
                        $isAuthorized = true;
                        \Log::info('Payment success - autorizacija kroz vremensku proveru (2 min)', [
                            'reservation_id' => $reservationId,
                            'created_at' => $reservation->created_at,
                            'minutes_ago' => $reservation->created_at->diffInMinutes(now()),
                            'reason' => 'Rezervacija kreirana u poslednjih 2 minuta'
                        ]);
                    }
                    
                    // TESTIRANJE: Ako postoji query parametar za testiranje
                    if (!$isAuthorized && $request->query('test_mode') === 'true') {
                        $isAuthorized = true;
                        \Log::info('Payment success - autorizacija kroz test mode', [
                            'reservation_id' => $reservationId,
                            'test_mode' => true
                        ]);
                    }
                }
                
                if ($isAuthorized) {
                    session(['last_merchant_transaction_id' => $reservation->merchant_transaction_id]);
                    \Log::info('Payment success - postavljen merchant_transaction_id u sesiju', [
                        'reservation_id' => $reservationId,
                        'merchant_transaction_id' => $reservation->merchant_transaction_id
                    ]);
                } else {
                    \Log::warning('Payment success - pokušaj pristupa tuđoj rezervaciji', [
                        'requested_reservation_id' => $reservationId,
                        'current_session_merchant_tx_id' => $currentSessionMerchantTxId,
                        'reservation_merchant_tx_id' => $reservation->merchant_transaction_id,
                        'client_ip' => $request->ip(),
                        'created_at' => $reservation->created_at
                    ]);
                    
                    return redirect('/')->with('error', 'Nije moguće pristupiti ovoj rezervaciji. Molimo kontaktirajte podršku.');
                }
            }
        }
        
        return view('payment.success', compact('reservationId'));
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