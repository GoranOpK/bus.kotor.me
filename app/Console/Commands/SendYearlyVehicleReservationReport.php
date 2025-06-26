<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReportService;
use App\Mail\YearlyVehicleReservationReportMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

/**
 * Komanda za automatsko slanje godišnjeg izvještaja o broju rezervacija po tipu vozila.
 */
class SendYearlyVehicleReservationReport extends Command
{
    // Pokreće se: php artisan reports:yearly-vehicle-reservations
    protected $signature = 'reports:yearly-vehicle-reservations';

    protected $description = 'Šalje godišnji izvještaj o broju rezervacija po tipu vozila na zadate email adrese';

    public function handle(ReportService $service)
    {
        // Prethodna godina
        $year = Carbon::now()->subYear()->year;

        // Dobavljanje broja rezervacija po tipu vozila za godinu
        $reservationsByType = $service->yearlyVehicleReservationsByType($year);

        // Definiši email adrese
        $emails = [
            'prihodi@kotor.me',
            'mirjana.grbovic@kotor.me',
            'informatika@kotor.me',
            // Dodaj još adresa po potrebi
        ];

        // Salji izvještaj svakoj adresi
        foreach ($emails as $email) {
            Mail::to($email)->send(new YearlyVehicleReservationReportMail($year, $reservationsByType));
        }

        $this->info('Godišnji izvještaj o rezervacijama po tipu vozila je poslat!');
    }
}