<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// Model za tabelu 'reservations' (rezervacije)
class Reservation extends Model
{
    protected $table = 'reservations';

    protected $fillable = [
        'drop_off_time_slot_id',    // ID vremenskog slota za iskrcaj putnika
        'pick_up_time_slot_id',     // ID vremenskog slota za ukrcaj putnika
        'reservation_date',         // Datum rezervacije
        'user_name',                // Ime korisnika koji pravi rezervaciju
        'country',                  // Država korisnika
        'license_plate',            // Registarski broj vozila
        'vehicle_type_id',          // ID tipa vozila
        'email',                    // Email korisnika
        'status',                   // Status rezervacije (npr. pending, confirmed, cancelled)
        'merchant_transaction_id',  // Merchant Transaction ID
        'fiscal_jir',               // JIKR
        'fiscal_ikof',              // IKOF
        'fiscal_qr',                // QR kod (URL)
        'fiscal_operator',          // ENUIdentifier
        'fiscal_date',              // Datum fiskalizacije
    ];

    protected $casts = [
        'reservation_date' => 'date',
    ];

    public function dropOffTimeSlot()
    {
        return $this->belongsTo(TimeSlot::class, 'drop_off_time_slot_id');
    }

    public function pickUpTimeSlot()
    {
        return $this->belongsTo(TimeSlot::class, 'pick_up_time_slot_id');
    }

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class, 'vehicle_type_id');
    }

    // Metod koji vraća cijenu rezervacije bez obzira na broj slotova
    public function getPrice()
    {
        return $this->vehicleType ? $this->vehicleType->price : 0;
    }
}