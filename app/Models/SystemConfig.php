<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    protected $table = 'system_config';
    public $timestamps = true; // koristi updated_at
    protected $fillable = ['name', 'value'];
} 