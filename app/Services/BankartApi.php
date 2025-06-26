<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class BankartApi
{
    protected static function getAuthHeaders()
    {
        // Prilagodi prema tvojoj integraciji (API key, basic auth...)
        $username = config('services.bankart.username');
        $password = config('services.bankart.password');
        $apiKey   = config('services.bankart.api_key');
        $sharedSecret = config('services.bankart.shared_secret');

        return [
            'Authorization' => 'Basic ' . base64_encode("$username:$password"),
            'X-API-KEY'     => $apiKey,
            'Content-Type'  => 'application/json',
            // Dodaj X-Signature ako Bankart to zahteva (pogledaj dokumentaciju)
        ];
    }

    /** Preautorizacija */
    public static function preauthorize(array $orderData)
    {
        $url = config('services.bankart.api_url') . '/preauthorize';
        $headers = self::getAuthHeaders();
        // Ako treba potpis: $headers['X-Signature'] = ...;

        $response = Http::withHeaders($headers)->post($url, $orderData);
        return $response->json();
    }

    /** Capture (naplata preautorizacije) */
    public static function capture($preauthId, $amount)
    {
        $url = config('services.bankart.api_url') . "/capture/$preauthId";
        $headers = self::getAuthHeaders();

        $payload = [
            'amount' => $amount
        ];

        $response = Http::withHeaders($headers)->post($url, $payload);
        return $response->json();
    }

    /** Void (otkazivanje preautorizacije) */
    public static function void($preauthId)
    {
        $url = config('services.bankart.api_url') . "/void/$preauthId";
        $headers = self::getAuthHeaders();

        $response = Http::withHeaders($headers)->post($url);
        return $response->json();
    }

    /** Refund */
    public static function refund($transactionId, $amount)
    {
        $url = config('services.bankart.api_url') . "/refund/$transactionId";
        $headers = self::getAuthHeaders();

        $payload = [
            'amount' => $amount
        ];

        $response = Http::withHeaders($headers)->post($url, $payload);
        return $response->json();
    }
}