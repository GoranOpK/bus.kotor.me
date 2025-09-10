<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TempData;

class CleanupExpiredSlotReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'slots:cleanup-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleanup expired slot reservations from temp_data table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredCount = TempData::where('reserved_until', '<', now())
            ->where('status', 'reserved')
            ->count();

        if ($expiredCount === 0) {
            $this->info('No expired slot reservations found.');
            return 0;
        }

        // Obriši istekle rezervacije
        $deleted = TempData::where('reserved_until', '<', now())
            ->where('status', 'reserved')
            ->delete();

        $this->info("Cleaned up {$deleted} expired slot reservations.");
        
        return 0;
    }
}
