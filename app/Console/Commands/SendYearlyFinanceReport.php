<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReportService;
use App\Mail\YearlyFinanceReportMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

/**
 * Komanda za automatsko slanje godišnjeg finansijskog izvještaja na email.
 * Pokreće se iz schedulera (Task Scheduler na Windowsu ili Laravel scheduler).
 */
class SendYearlyFinanceReport extends Command
{
    // Naziv komande koju pokrećeš iz terminala: php artisan reports:yearly-finance
    protected $signature = 'reports:yearly-finance';

    protected $description = 'Šalje godišnji finansijski izvještaj na zadate email adrese';

    public function handle(ReportService $service)
    {
        // Dobijanje prethodne godine (da bi izvještaj bio za cijelu proteklu godinu)
        $year = Carbon::now()->subYear()->year;

        // Dobavljanje ukupne zarade po mjesecima za izvještaj (kolekcija sa ['mjesec', 'prihod'])
        $financePerMonth = $service->yearlyFinancePerMonth($year);

        // Izračun ukupne godišnje zarade
        $totalFinance = $financePerMonth->sum('prihod');

        // Definiši ili učitaj iz configa/baze email adrese kome šalješ izvještaj
        $emails = [
            'prihodi@kotor.me',
            'mirjana.grbovic@kotor.me',
            'informatika@kotor.me',
            // Dodaj još email adresa po potrebi
        ];

        // Slanje maila svim navedenim adresama
        foreach ($emails as $email) {
            Mail::to($email)->send(new YearlyFinanceReportMail($year, $financePerMonth, $totalFinance));
        }

        $this->info('Godišnji finansijski izvještaj je poslat!');
    }
}