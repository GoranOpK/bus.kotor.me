<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Putanje za CORS
    |--------------------------------------------------------------------------
    |
    | Ovdje definišeš na koje rute se CORS pravila odnose.
    | Najčešće je to 'api/*' da bi se pravila odnosila samo na API rute,
    | a ne na cijeli sajt. 'sanctum/csrf-cookie' koristiš ako koristiš Sanctum
    | za autentifikaciju.
    */
    'paths' => ['api/*', 'sanctum/csrf-cookie' , 'procesiraj-placanje', 'payment/*', 'callback', 'callback/*', 'test-callback'],

    /*
    |--------------------------------------------------------------------------
    | Dozvoljene HTTP metode
    |--------------------------------------------------------------------------
    |
    | Ovdje navodiš koje HTTP metode su dozvoljene sa drugih domena (GET, POST, PUT, PATCH, DELETE, OPTIONS).
    | '*' NIJE preporučeno kad koristiš supports_credentials=true.
    | Navedi eksplicitno sve metode koje koristiš.
    */
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    /*
    |--------------------------------------------------------------------------
    | Dozvoljeni origini (domeni)
    |--------------------------------------------------------------------------
    |
    | Ovdje navodiš sa kojih domena/portova dozvoljavaš pristup tvom API-ju.
    | Nikad ne koristi '*' u produkciji, već navedi tačno domene koje dozvoljavaš.
    | Dodaj ovdje i lokalne i mrežne adrese koje koristiš za razvoj i produkciju.
    */
    'allowed_origins' => [
        'http://localhost:8000',
        'https://localhost:8000',
        'http://localhost:8080',
        'https://localhost:8080',
        'http://127.0.0.1:8000',
        'https://127.0.0.1:8000',
        'http://127.0.0.1:8080',
        'https://127.0.0.1:8080',
        'https://bus.kotor.me', // Produkcija
        'https://gateway.bankart.si', // Bankart payment gateway
        'https://bankart.si', // Bankart main domain
    ],

    /*
    |--------------------------------------------------------------------------
    | Dozvoljeni origin paterni
    |--------------------------------------------------------------------------
    |
    | Ako koristiš wildcard domene (npr. *.mojsajt.com), ovdje možeš navesti regex.
    | U većini slučajeva ovo može ostati prazno.
    */
    'allowed_origins_patterns' => [],

    /*
    |--------------------------------------------------------------------------
    | Dozvoljena zaglavlja
    |--------------------------------------------------------------------------
    |
    | Ovdje navodiš koja HTTP zaglavlja frontend može slati.
    | '*' NIJE preporučeno kad koristiš supports_credentials=true.
    | Navedi tipična zaglavlja koja koristiš u aplikaciji.
    */
    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'Authorization',
        'Accept',
        'Origin',
		'X-XSRF-TOKEN', // Milenko ovo dodao na nagovor njegovog kopileta
    ],

    /*
    |--------------------------------------------------------------------------
    | Izložena zaglavlja
    |--------------------------------------------------------------------------
    |
    | Ovdje određuješ koja zaglavlja frontend može da pročita iz odgovora.
    | Ako ti ništa specijalno ne treba, može ostati prazno.
    */
    'exposed_headers' => [],

    /*
    |--------------------------------------------------------------------------
    | Maksimalno trajanje CORS preflight zahtjeva (u sekundama)
    |--------------------------------------------------------------------------
    |
    | Koliko dugo browser može da kešira odgovor na preflight OPTIONS zahtjev.
    | 0 znači da se svaki put šalje novi preflight zahtjev.
    */
    'max_age' => 0,

    /*
    |--------------------------------------------------------------------------
    | Podrška za kredencijale (cookies, auth...)
    |--------------------------------------------------------------------------
    |
    | Ako koristiš autentifikaciju/cookies između domena, ovo treba biti true.
    | Ako ne koristiš, može biti false.
    */
    'supports_credentials' => true,

];