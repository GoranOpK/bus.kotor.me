<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Podrazumijevana autentikacija
    |--------------------------------------------------------------------------
    |
    | Ovdje određuješ koji "guard" i "broker" za reset lozinke su podrazumijevani
    | za tvoju aplikaciju. Ovdje je guard 'web', a broker 'admins'.
    |
    */

    'defaults' => [
        'guard' => 'web', // Podrazumijevani guard je web (sesija)
        'passwords' => 'admins', // Podrazumijevani broker za reset lozinke je admins
    ],

    /*
    |--------------------------------------------------------------------------
    | Guard-ovi za autentikaciju
    |--------------------------------------------------------------------------
    |
    | Ovdje definišeš guard-ove (način autentikacije). Najčešće koristiš
    | 'web' guard za prijavu preko browsera (sesija).
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session', // koristi PHP sesije za autentikaciju
            'provider' => 'admins', // koristi provider admins (vidi ispod)
        ],
        'api' => [
            'driver' => 'token', // ili 'sanctum' ili 'jwt' ako koristiš neki paket
            'provider' => 'admins',
        ],
        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
        'readonly' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider-i korisnika
    |--------------------------------------------------------------------------
    |
    | Provider određuje kako se podaci o korisnicima dobijaju iz baze.
    | Ovdje koristiš Eloquent model Admin i tabelu admins.
    |
    */

    'providers' => [
        'admins' => [
            'driver' => 'eloquent', // koristi Eloquent ORM
            'model' => App\Models\Admin::class, // koristi model Admin
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetovanje lozinke
    |--------------------------------------------------------------------------
    |
    | Ovdje podešavaš opcije za resetovanje lozinke (tabela za tokene itd).
    | 'provider' treba da bude isti kao iznad (admins).
    |
    */

    'passwords' => [
        'admins' => [
            'provider' => 'admins', // koristi provider admins
            'table' => 'password_reset_tokens', // tabela za reset tokene
            'expire' => 60, // token važi 60 minuta
            'throttle' => 60, // možeš tražiti novi token na svakih 60 sekundi
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout za potvrdu lozinke
    |--------------------------------------------------------------------------
    |
    | Koliko dugo (u sekundama) važi potvrda lozinke prije nego što moraš
    | ponovo da uneseš šifru (default je 3 sata).
    |
    */

    'password_timeout' => 10800,

];