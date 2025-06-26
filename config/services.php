<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | Ovdje se nalaze podešavanja (credentials) za integraciju sa raznim
    | eksternim servisima kao što su Mailgun, Postmark, AWS SES, Slack itd.
    | Ova lokacija je standardna za Laravel, tako da paketi i tvoj kod
    | mogu lako pronaći sve neophodne podatke za povezivanje sa servisima.
    |
    */

    'bankart' => [
        'api_key'    => env('BANKART_API_KEY'),
        'username'   => env('BANKART_API_USERNAME'),
        'password'   => env('BANKART_API_PASSWORD'),
        'api_url'    => env('BANKART_API_URL'),
        'shared_secret' => env('BANKART_SHARED_SECRET'),
        'signature_enabled' => env('BANKART_SIGNATURE_ENABLED', false),
    ],

    // Resend servis za slanje e-pošte
    'resend' => [
        'key' => env('RESEND_KEY'), // API ključ iz .env
    ],

];