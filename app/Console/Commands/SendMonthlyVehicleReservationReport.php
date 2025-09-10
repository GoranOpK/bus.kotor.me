<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReportService;
use App\Mail\MonthlyVehicleReservationReportMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

/**
 * Komanda za automatsko slanje mjesečnog izvještaja o broju rezervacija po tipu vozila.
 */
class SendMonthlyVehicleReservationReport extends Command
{
    // Pokreće se: php artisan reports:monthly-vehicle-reservations
    protected $signature = 'reports:monthly-vehicle-reservations';

    protected $description = 'Šalje mjesečni izvještaj o broju rezervacija po tipu vozila na zadate email adrese';

    public function handle(ReportService $service)
    {
        // Predhodni mjesec
        $month = Carbon::now()->subMonth()->format('m');
        $year = Carbon::now()->subMonth()->year;

        // Dobavljanje broja rezervacija po tipu vozila za mjesec
        $reservationsByType = $service->monthlyVehicleReservationsByType($month, $year);

        // Definiši email adrese
        $emails = [
            'prihodi@kotor.me',
            'mirjana.grbovic@kotor.me',
            'informatika@kotor.me',
            // Dodaj još adresa po potrebi
        ];

        // Salji izvještaj svakoj adresi
        foreach ($emails as $email) {
            Mail::to($email)->send(new MonthlyVehicleReservationReportMail($month, $year, $reservationsByType));
        }

        $this->info('Mjesečni izvještaj o rezervacijama po tipu vozila je poslat!');
    }
}