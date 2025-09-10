<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReportService;
use App\Mail\MonthlyFinanceReportMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

/**
 * Komanda za automatsko slanje mjesečnog finansijskog izvještaja na email.
 * Pokreće se iz schedulera (Task Scheduler na Windowsu ili Laravel scheduler).
 */
class SendMonthlyFinanceReport extends Command
{
    // Naziv komande koju pokrećeš iz terminala: php artisan reports:monthly-finance
    protected $signature = 'reports:monthly-finance';

    protected $description = 'Šalje mjesečni finansijski izvještaj na zadate email adrese';

    public function handle(ReportService $service)
    {
        // Dobijanje prethodnog mjeseca i godine (da bi izvještaj bio za kompletan mjesec)
        $date = Carbon::now()->subMonth();
        $month = $date->month;
        $year = $date->year;

        // Dobavljanje ukupne zarade za izvještaj
        $finance = $service->monthlyFinancialReport($month, $year);

        // Definiši ili učitaj iz configa/baze email adrese kome šalješ izvještaj
        $emails = [
            'prihodi@kotor.me',
            'mirjana.grbovic@kotor.me',
            'informatika@kotor.me',
            // Dodaj još email adresa po potrebi
        ];

        // Slanje maila svim navedenim adresama
        foreach ($emails as $email) {
            Mail::to($email)->send(new MonthlyFinanceReportMail($month, $year, $finance));
        }

        $this->info('Mjesečni finansijski izvještaj je poslat!');
    }
}