<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Registruje prilagođene Artisan komande aplikacije.
     * (Nije obavezno navoditi ako su u app/Console/Commands i koristite Laravel 8+)
     */
    protected $commands = [
        // \App\Console\Commands\SendDailyFinanceReport::class,
        // \App\Console\Commands\SendMonthlyFinanceReport::class,
        // \App\Console\Commands\SendYearlyFinanceReport::class,
        // \App\Console\Commands\SendDailyVehicleReservationReport::class,
        // \App\Console\Commands\SendMonthlyVehicleReservationReport::class,
        // \App\Console\Commands\SendYearlyVehicleReservationReport::class,
    ];

    /**
     * Definiše raspored automatskog pokretanja izvještaja.
     */
    protected function schedule(Schedule $schedule)
    {
        // Dnevni finansijski izvještaj – svaki dan u 20:30
        $schedule->command('reports:daily-finance')->dailyAt('20:30');

        // Mjesečni finansijski izvještaj – 1. u mjesecu u 06:30 (za prethodni mjesec)
        $schedule->command('reports:monthly-finance')->monthlyOn(1, '06:30');

        // Godišnji finansijski izvještaj – 1. januara u 06:30 (za prethodnu godinu)
        $schedule->command('reports:yearly-finance')->yearlyOn(1, 1, '06:30');

        // Dnevni izvještaj o rezervacijama po tipu vozila – svaki dan u 23:55
        $schedule->command('reports:daily-vehicle-reservations')->dailyAt('23:55');

        // Mjesečni izvještaj o rezervacijama po tipu vozila – 1. u mjesecu u 06:31 (za prethodni mjesec)
        $schedule->command('reports:monthly-vehicle-reservations')->monthlyOn(1, '06:31');

        // Godišnji izvještaj o rezervacijama po tipu vozila – 1. januara u 06:31 (za prethodnu godinu)
        $schedule->command('reports:yearly-vehicle-reservations')->yearlyOn(1, 1, '06:31');
    }

    /**
     * Automatski učitava sve komande iz app/Console/Commands.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}