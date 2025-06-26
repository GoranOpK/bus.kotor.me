<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\TempData;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

class PaymentController extends Controller
{
    public function redirectToHpp(Request $request)
    {

        $merchantTransactionId = $request->input('merchantTransactionId');
        if (!$merchantTransactionId) {
            return response()->json(['error' => 'Nedostaje merchantTransactionId.'], 400);
        }

        $temp = TempData::where('merchant_transaction_id', $merchantTransactionId)->first();
        if (!$temp) {
            return response()->json(['error' => 'Privremeni podaci nisu pronađeni.'], 404);
        }

        $amount = \App\Models\VehicleType::find($temp->vehicle_type_id)?->price ?? null;
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
            'callbackUrl'           => route('payment.callback', [], true),
            'customer' => [
                'billingAddress1' => 'Test street 1',
                'billingCity'     => 'Kotor',
                'billingCountry'  => 'ME',
                'billingPostcode' => '85330',
                'email'           => 'test@example.com',
            ],
        ];

        $apiKey    = config('services.bankart.api_key');
        $username  = config('services.bankart.username');
        $password  = config('services.bankart.password');
        $apiUrl    = rtrim(config('services.bankart.api_url'), '/') . "/transaction/{$apiKey}/debit";

        $contentType = 'application/json; charset=utf-8';

        $headers = [
            'Content-Type' => $contentType,
            'Accept'       => 'application/json',
        ];

        if (config('services.bankart.signature_enabled', false)) {
            $sharedSecret = config('services.bankart.shared_secret');
            $bodyRaw = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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
     * Callback endpoint koji Bankart poziva nakon transakcije.
     * Ovde NIŠTA ne radiš direktno sa reservations!
     * Pozivaš ReservationController@storeFromTemp!
     */
    public function callback(Request $request)
    {
        $payload = $request->getContent();
        $headers = $request->headers;
        $sharedSecret = config('services.bankart.shared_secret');
        $signature = $headers->get('x-signature') ?? $headers->get('X-Signature');

        Log::info('Bankart callback received', [
            'headers' => $headers->all(),
            'payload' => $payload
        ]);

        if (config('services.bankart.signature_enabled', false)) {
            // Sklapanje signature kao za outgoing!
            $contentType = 'application/json; charset=utf-8';
            $date = $headers->get('x-date') ?? $headers->get('date');
            $uri = $request->getRequestUri(); // Ovo uključuje path i eventualni query string
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