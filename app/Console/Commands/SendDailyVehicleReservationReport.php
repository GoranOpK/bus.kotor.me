<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReportService;
use App\Mail\DailyVehicleReservationReportMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

/**
 * Komanda za automatsko slanje dnevnog izvještaja o broju rezervacija po tipu vozila.
 */
class SendDailyVehicleReservationReport extends Command
{
    // Naziv komande: php artisan reports:daily-vehicle-reservations
    protected $signature = 'reports:daily-vehicle-reservations';

    protected $description = 'Šalje dnevni izvještaj o broju rezervacija po tipu vozila na zadate email adrese';

    public function handle(ReportService $service)
    {
        // Dobijanje datuma za prethodni dan
        $date = Carbon::yesterday()->toDateString();

        // Dobavljanje broja rezervacija po tipu vozila za taj dan
        $reservationsByType = $service->dailyVehicleReservationsByType($date);

        // Definisanje email adresa za slanje izvještaja
        $emails = [
            'prihodi@kotor.me',
            'mirjana.grbovic@kotor.me',
            'informatika@kotor.me',
            // Dodaj još adresa po potrebi
        ];

        // Slanje emaila svim navedenim adresama
        foreach ($emails as $email) {
            Mail::to($email)->send(new DailyVehicleReservationReportMail($reservationsByType, $date));
        }

        $this->info('Dnevni izvještaj o rezervacijama po tipu vozila je poslat!');
    }
}