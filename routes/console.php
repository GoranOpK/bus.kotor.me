<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Registrujemo komande za izvještaje
Artisan::command('reports:daily-finance', function () {
    $reportService = new \App\Services\ReportService();
    $yesterday = now()->subDay()->format('Y-m-d');
    $reportService->sendDailyFinancialReport($yesterday);
    $this->info("Daily finance report sent for {$yesterday}");
})->purpose('Send daily finance report');

Artisan::command('reports:monthly-finance', function () {
    $reportService = new \App\Services\ReportService();
    $lastMonth = now()->subMonth();
    $reportService->sendMonthlyFinancialReport($lastMonth->month, $lastMonth->year);
    $this->info("Monthly finance report sent for {$lastMonth->format('Y-m')}");
})->purpose('Send monthly finance report');

Artisan::command('reports:yearly-finance', function () {
    $reportService = new \App\Services\ReportService();
    $lastYear = now()->subYear()->year;
    $reportService->sendYearlyFinancialReport($lastYear);
    $this->info("Yearly finance report sent for {$lastYear}");
})->purpose('Send yearly finance report');

Artisan::command('reports:daily-vehicle-reservations', function () {
    $reportService = new \App\Services\ReportService();
    $yesterday = now()->subDay()->format('Y-m-d');
    $reportService->sendDailyVehicleTypeReport($yesterday);
    $this->info("Daily vehicle reservations report sent for {$yesterday}");
})->purpose('Send daily vehicle reservations report');

Artisan::command('reports:monthly-vehicle-reservations', function () {
    $reportService = new \App\Services\ReportService();
    $lastMonth = now()->subMonth();
    $reportService->sendMonthlyVehicleTypeReport($lastMonth->month, $lastMonth->year);
    $this->info("Monthly vehicle reservations report sent for {$lastMonth->format('Y-m')}");
})->purpose('Send monthly vehicle reservations report');

Artisan::command('reports:yearly-vehicle-reservations', function () {
    $reportService = new \App\Services\ReportService();
    $lastYear = now()->subYear()->year;
    $reportService->sendYearlyVehicleTypeReport($lastYear);
    $this->info("Yearly vehicle reservations report sent for {$lastYear}");
})->purpose('Send yearly vehicle reservations report');
