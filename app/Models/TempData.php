<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TempData extends Model
{
    protected $table = 'temp_data';

    protected $fillable = [
        'merchant_transaction_id',
        'drop_off_time_slot_id',
        'pick_up_time_slot_id',
        'reservation_date',
        'user_name',
        'country',
        'license_plate',
        'vehicle_type_id',
        'email',
        'status',
        'fiscal_jir',
        'fiscal_ikof',
        'fiscal_qr',
        'fiscal_operator',
        'fiscal_date',
        'availability_checked_at',
        'drop_off_remaining_at_check',
        'pick_up_remaining_at_check',
        'reserved_until',
        'user_language'
    ];

    public $timestamps = true; // ili false, ako ne koristiš created_at/updated_at
}