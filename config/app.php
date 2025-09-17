<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ime aplikacije
    |--------------------------------------------------------------------------
    |
    | Ova vrijednost predstavlja ime vaše aplikacije koje se koristi kada
    | Laravel treba da prikaže ime aplikacije u notifikacijama ili drugim
    | UI elementima gdje je potrebno prikazati ime aplikacije.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Okruženje aplikacije
    |--------------------------------------------------------------------------
    |
    | Ova vrijednost određuje "okruženje" u kojem aplikacija trenutno radi.
    | Na osnovu ove vrijednosti možete konfigurirati različite servise koje
    | aplikacija koristi. Postavite ovu vrijednost u ".env" fajlu.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Debug mod
    |--------------------------------------------------------------------------
    |
    | Kada je aplikacija u debug modu, detaljne poruke o greškama sa stack
    | tragovima će biti prikazane pri svakoj grešci. Ako je isključeno,
    | prikazivaće se generična stranica greške.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | URL aplikacije
    |--------------------------------------------------------------------------
    |
    | Ovaj URL koristi se od strane konzole za pravilno generisanje URL-ova
    | pri korišćenju Artisan komandi. Trebalo bi da postavite ovu vrijednost
    | na osnovni URL vaše aplikacije.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Vremenska zona
    |--------------------------------------------------------------------------
    |
    | Ovdje možete specificirati podrazumijevanu vremensku zonu za vašu
    | aplikaciju, koja će biti korišćena od strane PHP datuma i vremenskih
    | funkcija. Podrazumijevana vrijednost je "UTC".
    |
    */

    'timezone' => 'Europe/Belgrade',

    /*
    |--------------------------------------------------------------------------
    | Lokalizacija aplikacije
    |--------------------------------------------------------------------------
    |
    | Lokalizacija aplikacije određuje podrazumijevani jezik koji će se
    | koristiti za prevode i metode lokalizacije Laravela. Ova opcija
    | može biti postavljena na bilo koji jezik za koji imate prevode.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Rezervni jezik
    |--------------------------------------------------------------------------
    |
    | Rezervni jezik se koristi kada trenutni jezik nije dostupan. Možete
    | promijeniti ovu vrijednost da odgovara bilo kojem jeziku koji postoji
    | u vašoj aplikaciji.
    |
    */

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Faker lokalizacija
    |--------------------------------------------------------------------------
    |
    | Ova lokalizacija koristi se od strane Faker PHP biblioteke kada
    | generiše lažne podatke za vaše baze podataka. Na primjer, koristi
    | se za generisanje lokalizovanih brojeva telefona, adresa itd.
    |
    */

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Ključ za šifrovanje
    |--------------------------------------------------------------------------
    |
    | Ovaj ključ koristi Laravel-ove servise šifrovanja i mora biti
    | nasumičan string od 32 karaktera kako bi svi šifrovani podaci bili
    | sigurni. Postavite ovu vrijednost prije objavljivanja aplikacije.
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Režim održavanja
    |--------------------------------------------------------------------------
    |
    | Ove opcije konfiguracije određuju drajver koji se koristi za
    | upravljanje "režimom održavanja" aplikacije. "Cache" drajver će
    | omogućiti kontrolu režima održavanja na više mašina.
    |
    | Podržani drajveri: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Servisni provajderi
    |--------------------------------------------------------------------------
    |
    | Ovdje se nalaze servisni provajderi koje će Laravel automatski učitati
    | prilikom pokretanja aplikacije. Možete dodati vaše sopstvene provajdere
    | kako biste proširili funkcionalnosti aplikacije.
    |
    */

    'providers' => [

        /*
         * Laravel-ovi servisni provajderi...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * Dodatni paket provajderi...
         */

        /*
         * Provajderi aplikacije...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,

    ],

    /*
    |--------------------------------------------------------------------------
    | Alias klase
    |--------------------------------------------------------------------------
    |
    | Ovaj niz aliasa klasa biće registrovan prilikom pokretanja aplikacije.
    | Slobodno dodajte što više želite jer se aliasi "lijeno" učitavaju i ne
    | utiču na performanse.
    |
    */

    'aliases' => [

        'App' => Illuminate\Support\Facades\App::class,
        'Arr' => Illuminate\Support\Arr::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Http' => Illuminate\Support\Facades\Http::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'Str' => Illuminate\Support\Str::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,

    ],

    /*
    |--------------------------------------------------------------------------
    | System Configuration
    |--------------------------------------------------------------------------
    |
    | Default vrednosti za system konfiguraciju
    |
    */

<<<<<<< HEAD
    'default_available_parking_slots' => env('DEFAULT_AVAILABLE_PARKING_SLOTS', 9),
=======
    'default_available_parking_slots' => env('DEFAULT_AVAILABLE_PARKING_SLOTS', 8),
>>>>>>> edd871dd4444f817be418d934462960767b66424

];