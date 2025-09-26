<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\SystemConfig;

class SlotService
{
    /**
     * Dohvata maksimalni kapacitet iz system_config tabele (cache-ovano)
     */
    private function getMaxCapacity()
    {
<<<<<<< HEAD
        $defaultValue = config('app.default_available_parking_slots', 9);
=======
<<<<<<< HEAD
        $defaultValue = config('app.default_available_parking_slots', 9);
=======
        $defaultValue = config('app.default_available_parking_slots', 8);
>>>>>>> edd871dd4444f817be418d934462960767b66424
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
        return cache()->remember('available_parking_slots', 300, function () use ($defaultValue) {
            return (int)SystemConfig::where('name', 'available_parking_slots')->value('value') ?: $defaultValue;
        });
    }

    public function getSlotsForDate($date)
    {
        $tableName = date('Ymd', strtotime($date));
        return DB::table($tableName)->get();
    }

    public function reserveSlot($date, $slotId)
    {
        $tableName = date('Ymd', strtotime($date));
        return DB::table($tableName)
            ->where('time_slot_id', $slotId)
            ->update(['remaining' => DB::raw('remaining - 1')]);
    }

    /**
     * Proverava dostupnost slotova za određeni datum
     * @param string $date Datum rezervacije (YYYY-MM-DD)
     * @param array $slotIds Array slot ID-jeva za proveru
     * @return array Asocijativni niz sa dostupnošću po slot ID-u
     */
    public function getSlotAvailability($date, $slotIds = [])
    {
        $tableName = date('Ymd', strtotime($date));
        $availability = [];
        
        try {
            // Proveri da li tabela postoji
            if (!$this->tableExists($tableName)) {
                \Log::info('SlotService - dinamička tabela ne postoji, kreiram je', [
                    'table_name' => $tableName,
                    'date' => $date
                ]);
                
                // Kreiraj tabelu ako ne postoji
                $this->createDynamicTable($tableName);
                
                \Log::info('SlotService - dinamička tabela kreirana', [
                    'table_name' => $tableName
                ]);
            }

            \Log::info('SlotService - proveravam dostupnost', [
                'table_name' => $tableName,
                'slot_ids' => $slotIds,
                'date' => $date
            ]);

            // Koristi direktan SQL upit za dinamičku tabelu SA ZAKLJUČAVANJEM
            if (empty($slotIds)) {
                // Vrati sve slotove ako nisu specificirani
                $slots = DB::select("SELECT * FROM `$tableName` ORDER BY time_slot_id FOR UPDATE");
            } else {
                // Vrati samo specificirane slotove SA ZAKLJUČAVANJEM
                $slotIdsStr = implode(',', $slotIds);
                $slots = DB::select("SELECT * FROM `$tableName` WHERE time_slot_id IN ($slotIdsStr) ORDER BY time_slot_id FOR UPDATE");
            }

            \Log::info('SlotService - dohvaćeni slotovi iz baze', [
                'table_name' => $tableName,
                'slots_count' => count($slots),
                'slots_data' => $slots
            ]);

            foreach ($slots as $slot) {
                $availability[$slot->time_slot_id] = [
                    'available' => $slot->remaining,
                    'remaining' => $slot->remaining,
                    'total_capacity' => $this->getMaxCapacity(),
                    'is_enabled' => (bool)$slot->available
                ];
            }

            // Dodaj nedostajuće slotove kao nedostupne
            foreach ($slotIds as $slotId) {
                if (!isset($availability[$slotId])) {
                    $availability[$slotId] = [
                        'available' => 0,
                        'remaining' => 0,
                        'total_capacity' => $this->getMaxCapacity(),
                        'is_enabled' => false
                    ];
                }
            }

            \Log::info('SlotService - finalna dostupnost', [
                'table_name' => $tableName,
                'availability' => $availability
            ]);

        } catch (\Exception $e) {
            \Log::error('SlotService getSlotAvailability error: ' . $e->getMessage(), [
                'table_name' => $tableName,
                'slot_ids' => $slotIds,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Ako dođe do greške, svi slotovi su nedostupni
            foreach ($slotIds as $slotId) {
                $availability[$slotId] = [
                    'available' => 0,
                    'remaining' => 0,
                    'total_capacity' => $this->getMaxCapacity(),
                    'is_enabled' => false
                ];
            }
        }

        return $availability;
    }

    /**
     * Proverava da li dinamička tabela postoji (kreira se kroz cron)
     * @param string $tableName Ime tabele (YYYYMMDD)
     */
    public function createDynamicTable($tableName)
    {
        try {
            // Konvertuj tableName nazad u datum za proceduru
            $date = \DateTime::createFromFormat('Ymd', $tableName);
            if (!$date) {
                throw new \Exception("Neispravan format tableName: $tableName");
            }
            
            $dateString = $date->format('Y-m-d');
            
            \Log::info('SlotService - proveravam da li tabela postoji', [
                'table_name' => $tableName,
                'date_string' => $dateString
            ]);
            
            // Proveri da li tabela postoji
            if (!$this->tableExists($tableName)) {
                \Log::warning('SlotService - tabela ne postoji, možda cron nije pokrenut', [
                    'table_name' => $tableName,
                    'date_string' => $dateString
                ]);
                
                // Ako tabela ne postoji, kreiraj je ručno
                DB::statement("CALL CreateTableForDateWithData(?)", [$dateString]);
                
                \Log::info('SlotService - tabela kreirana ručno', [
                    'table_name' => $tableName,
                    'date_string' => $dateString
                ]);
            } else {
                \Log::info('SlotService - tabela već postoji', [
                    'table_name' => $tableName,
                    'date_string' => $dateString
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('SlotService createDynamicTable error: ' . $e->getMessage(), [
                'table_name' => $tableName,
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Proverava da li dinamička tabela postoji
     * @param string $tableName Ime tabele
     * @return bool
     */
    public function tableExists($tableName)
    {
        try {
            $result = DB::select("SHOW TABLES LIKE '$tableName'");
            $exists = !empty($result);
            
            \Log::info('SlotService tableExists check', [
                'table_name' => $tableName,
                'result' => $result,
                'exists' => $exists
            ]);
            
            return $exists;
        } catch (\Exception $e) {
            \Log::error('SlotService tableExists error: ' . $e->getMessage(), [
                'table_name' => $tableName
            ]);
            return false;
        }
    }

    /**
     * Ažurira dostupnost slotova u dinamičkoj tabeli koristeći procedure
     * @param string $date Datum rezervacije
     * @param int $slotId ID slota
     * @param int $change Promena u dostupnosti (+1 za oslobađanje, -1 za rezervaciju)
     * @return bool
     */
    public function updateSlotAvailability($date, $slotId, $change = -1)
    {
        $tableName = date('Ymd', strtotime($date));
        
        try {
            if (!$this->tableExists($tableName)) {
                return false;
            }

            // Koristi direktan SQL umesto Eloquent-a za dinamičke tabele
            $sql = "UPDATE `$tableName` SET 
                    remaining = remaining + ?, 
                    available = CASE WHEN remaining + ? > 0 THEN 1 ELSE 0 END 
                    WHERE time_slot_id = ? AND remaining > 0";
            
            $affected = DB::update($sql, [$change, $change, $slotId]);

            \Log::info('SlotService updateSlotAvailability', [
                'table_name' => $tableName,
                'slot_id' => $slotId,
                'change' => $change,
                'affected_rows' => $affected
            ]);

            return $affected > 0;
        } catch (\Exception $e) {
            \Log::error("Error updating slot availability: " . $e->getMessage(), [
                'table_name' => $tableName,
                'slot_id' => $slotId,
                'change' => $change
            ]);
            return false;
        }
    }
}