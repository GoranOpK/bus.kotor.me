<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // Dodaj ovo za API token autentikaciju
use Spatie\Permission\Traits\HasRoles; // Trait za rad sa ulogama i dozvolama (Spatie)
use Illuminate\Support\Str;

class Admin extends Authenticatable
{
    use HasApiTokens; // Omogućava kreiranje i validaciju API tokena (Sanctum)
    use Notifiable;
    use HasRoles; // Omogućava dodjelu uloga i permisija adminima

    /**
     * Polja koja se mogu masovno popunjavati.
     */
    protected $fillable = [
        'username',          // Ime admina
        'email',             // Email adresa
        'password',          // Lozinka
        'role',              // Polje za ulogu (nije obavezno ako koristiš Spatie)
    ];

    /**
     * Polja koja se sakrivaju prilikom serijalizacije modela (npr. API odgovor).
     */
    protected $hidden = [
        'password',          // Lozinka se ne prikazuje
        'remember_token',    // Token za "zapamti me" funkciju
    ];

    /**
     * Automatsko kastovanje polja na odgovarajući tip.
     */
    protected $casts = [
        'email_verified_at' => 'datetime', // Datum verifikacije emaila
    ];

    /**
     * Mutator: prilikom postavljanja lozinke automatski se šifruje.
     */
    public function setPasswordAttribute($value)
    {
        // Dodaj provjeru da li je već hešovano da ne bi duplo hešovao
        if (\Illuminate\Support\Str::startsWith($value, '$2y$')) {
            $this->attributes['password'] = $value;
        } else {
            $this->attributes['password'] = bcrypt($value);
        }
    }

    /**
     * Relacija: jedan admin može imati više rezervacija.
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class); // Povezivanje sa Reservation modelom
    }
}