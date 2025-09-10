<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Podrazumijevani Session Driver
    |--------------------------------------------------------------------------
    |
    | Ovdje biraš kako će Laravel čuvati korisničke sesije (podatke o loginu,
    | korpi itd.). Najčešće je to "file" ili "database". Ako koristiš "database",
    | moraš imati odgovarajuću tabelu u bazi.
    |
    | Podržano: "file", "cookie", "database", "memcached",
    |            "redis", "dynamodb", "array"
    |
    */

    'driver' => env('SESSION_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Trajanje Sesije
    |--------------------------------------------------------------------------
    |
    | Koliko minuta će sesija biti aktivna dok korisnik ne radi ništa.
    | Nakon tog vremena, korisnik se automatski izloguje.
    |
    */

    'lifetime' => (int) env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false), // Ako je true, sesija ističe kad se zatvori browser

    /*
    |--------------------------------------------------------------------------
    | Enkripcija Sesije
    |--------------------------------------------------------------------------
    |
    | Ako uključiš ovu opciju, svi podaci iz sesije će biti šifrovani na disku ili u bazi.
    | Preporučeno je ostaviti false osim ako baš imaš osjetljive podatke.
    |
    */

    'encrypt' => env('SESSION_ENCRYPT', false),

    /*
    |--------------------------------------------------------------------------
    | Lokacija za File Session Driver
    |--------------------------------------------------------------------------
    |
    | Ako koristiš "file" kao session driver, ovdje se čuvaju svi session fajlovi.
    | Podrazumijevano je storage/framework/sessions.
    |
    */

    'files' => storage_path('framework/sessions'),

    /*
    |--------------------------------------------------------------------------
    | Session Database Connection
    |--------------------------------------------------------------------------
    |
    | Ako koristiš "database" ili "redis" kao session driver, ovdje možeš izabrati
    | koju konekciju iz config/database.php koristiš.
    |
    */

    'connection' => env('SESSION_CONNECTION'),

    /*
    |--------------------------------------------------------------------------
    | Ime Tabele za Sesije
    |--------------------------------------------------------------------------
    |
    | Ako koristiš "database" kao session driver, ovdje zadaješ ime tabele u bazi.
    | Podrazumijevana je "sessions".
    |
    */

    'table' => env('SESSION_TABLE', 'sessions'),

    /*
    |--------------------------------------------------------------------------
    | Cache Store za Sesiju
    |--------------------------------------------------------------------------
    |
    | Ako koristiš neki od cache baziranih session drivera (redis, memcached itd.),
    | ovdje možeš izabrati koji cache "store" iz config/cache.php koristiš.
    |
    | Važi za: "apc", "dynamodb", "memcached", "redis"
    |
    */

    'store' => env('SESSION_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Šansa za Čišćenje Starih Sesija
    |--------------------------------------------------------------------------
    |
    | Neki session driveri samo povremeno brišu stare sesije. Ovdje zadaješ
    | vjerovatnoću da će se "čišćenje" desiti na svakom requestu.
    | [2, 100] znači 2% šansa.
    |
    */

    'lottery' => [2, 100],

    /*
    |--------------------------------------------------------------------------
    | Ime Session Cookie-a
    |--------------------------------------------------------------------------
    |
    | Ovo je ime kolačića (cookie) u browseru korisnika gdje se čuva ID sesije.
    | Obično nema potrebe mijenjati ovo.
    |
    */

    'cookie' => env(
        'SESSION_COOKIE',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_session'
    ),

    /*
    |--------------------------------------------------------------------------
    | Putanja za Session Cookie
    |--------------------------------------------------------------------------
    |
    | Ova putanja određuje gdje je cookie dostupan. Najčešće je to "/" (za cijeli sajt).
    |
    */

    'path' => env('SESSION_PATH', '/'),

    /*
    |--------------------------------------------------------------------------
    | Domen za Session Cookie
    |--------------------------------------------------------------------------
    |
    | Ovdje možeš ograničiti na koji domen/subdomen važi session cookie.
    | Najčešće ostaviš prazno ili podrazumijevano.
    |
    */

    'domain' => env('SESSION_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Samo HTTPS Session Cookie
    |--------------------------------------------------------------------------
    |
    | Ako je true, session cookie će se slati samo preko HTTPS konekcije.
    | Preporučeno za produkciju sa SSL-om.
    |
    */

    'secure' => env('SESSION_SECURE_COOKIE', true), // treba biti true za HTTPS

    /*
    |--------------------------------------------------------------------------
    | Samo HTTP Pristup Cookie-u
    |--------------------------------------------------------------------------
    |
    | Ako je true, JS iz browsera ne može pristupiti cookie-u (samo server-side).
    | Preporučeno ostaviti true radi sigurnosti.
    |
    */

    'http_only' => env('SESSION_HTTP_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Same-Site Cookie Opcija
    |--------------------------------------------------------------------------
    |
    | Ova opcija pomaže u zaštiti od CSRF napada. "lax" je podrazumijevano.
    | Može biti: "lax", "strict", "none", null
    |
    */

    'same_site' => 'lax',

    /*
    |--------------------------------------------------------------------------
    | Partitioned Cookies (Napredno)
    |--------------------------------------------------------------------------
    |
    | Ako je true, cookie je vezan za top-level site u cross-site situacijama.
    | Potrebno je secure + same-site none.
    |
    */

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),

];