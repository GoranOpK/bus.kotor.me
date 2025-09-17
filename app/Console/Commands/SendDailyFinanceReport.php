<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ReportService;
use App\Mail\DailyFinanceReportMail;
use Illuminate\Support\Facades\Mail;

/**
 * Komanda za automatsko slanje dnevnog izvještaja na email.
 * Pokreće se iz schedulera (Task Scheduler na Windowsu ili Laravel scheduler).
 */
class SendDailyFinanceReport extends Command
{
    // Naziv komande koju pokrećeš iz terminala: php artisan reports:daily
    protected $signature = 'reports:daily-finance';

    protected $description = 'Šalje dnevni finansijski izvještaj na zadate email adrese';

    public function handle(ReportService $service)
    {
        $date = now()->toDateString();

        // Dobavljanje podataka za izvještaj
        $finance = $service->dailyFinancialReport($date);
        $count = $service->dailyCount($date);

        // Definiši ili učitaj iz configa/baze email adrese kome šalješ izvještaj
        $emails = [
            'prihodi@kotor.me',
            'mirjana.grbovic@kotor.me',
            'informatika@kotor.me',
            
            // Dodaj još email adresa po potrebi
        ];

        // Slanje maila svim navedenim adresama
        foreach ($emails as $email) {
            Mail::to($email)->send(new DailyFinanceReportMail($date, $finance, $count));
        }

        $this->info('Dnevni finansijski izvještaj je poslat!');
    }
}