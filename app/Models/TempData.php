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
<<<<<<< HEAD
        'status',
        'fiscal_jir',
        'fiscal_ikof',
        'fiscal_qr',
        'fiscal_operator',
        'fiscal_date',
=======
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
    ];

    public $timestamps = true; // ili false, ako ne koristiš created_at/updated_at
}