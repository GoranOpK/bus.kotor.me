<?php

namespace App\Http\Controllers;

use App\Models\SystemConfig;
use Illuminate\Http\Request;

class SystemConfigController extends Controller
{
    /**
     * Dohvata vrednost available_parking_slots iz system_config tabele
     */
    public function getAvailableParkingSlots()
    {
<<<<<<< HEAD
        $defaultValue = config('app.default_available_parking_slots', 9);
=======
        $defaultValue = config('app.default_available_parking_slots', 8);
>>>>>>> edd871dd4444f817be418d934462960767b66424
        
        try {
            $value = SystemConfig::where('name', 'available_parking_slots')->value('value');
            
            return response()->json([
                'success' => true,
                'value' => (int)$value ?: $defaultValue,
                'name' => 'available_parking_slots'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching available parking slots: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'value' => $defaultValue,
                'error' => 'Failed to fetch available parking slots'
            ], 500);
        }
    }
}