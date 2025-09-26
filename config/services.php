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

    'fiscal' => [
        'api_url' => env('FISCAL_API_URL', 'https://pm-api-elektronskafiskalizacija.azurewebsites.net'),
        'api_token' => env('FISCAL_API_TOKEN'),
        'enu_identifier' => env('FISCAL_ENU_IDENTIFIER', 'hd772pw138'),
        'user_code' => env('FISCAL_USER_CODE', 'kl099vk702'),
        'user_name' => env('FISCAL_USER_NAME', 'OGNJEN VUKASOVIC'),
        'seller_name' => env('FISCAL_SELLER_NAME', 'OPŠTINA KOTOR'),
        'seller_id_type' => env('FISCAL_SELLER_ID_TYPE', 'TIN'),
        'seller_id_value' => env('FISCAL_SELLER_ID_VALUE', '02012936'),
        'seller_address' => env('FISCAL_SELLER_ADDRESS', 'Kotor'),
    ],

];