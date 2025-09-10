<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;

class BankartDebitService
{
    protected $client;
    protected $apiKey;
    protected $sharedSecret;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://gateway.bankart.si/api/v3/',
            'timeout'  => 15.0,
        ]);
        $this->apiKey = env('BANKART_API_KEY');
        $this->sharedSecret = env('BANKART_SHARED_SECRET');
    }

    /**
     * Debit (charge) payment via Bankart
     */
    public function debit(array $payload)
    {
        $headers = [
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            // Dodaj dodatne zaglavlja ako banka traži, npr. za autentikaciju
            // 'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->sharedSecret),
        ];

        try {
            $response = $this->client->request(
                'POST',
                "transaction/{$this->apiKey}/debit",
                [
                    'headers' => $headers,
                    'json'    => $payload,
                ]
            );
            return json_decode($response->getBody()->getContents(), true);
        } catch (BadResponseException $e) {
            // Vrati detalje greške iz odgovora Bankart API-ja, ako postoje
            if ($e->hasResponse()) {
                return json_decode($e->getResponse()->getBody()->getContents(), true);
            }
            // U suprotnom, vrati osnovnu poruku
            return ['error' => $e->getMessage()];
        }
    }
}