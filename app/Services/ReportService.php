<?php

namespace App\Services;

use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

// Importujemo posebne mail klase za sve izvještaje
use App\Mail\DailyVehicleReservationReportMail;
use App\Mail\MonthlyVehicleReservationReportMail;
use App\Mail\YearlyVehicleReservationReportMail;
use App\Mail\DailyFinanceReportMail;
use App\Mail\MonthlyFinanceReportMail;
use App\Mail\YearlyFinanceReportMail;

class ReportService
{
    // Dohvata email adrese iz tabele report_emails
    protected function getReportEmails()
    {
        return \DB::table('report_emails')->pluck('email')->toArray();
    }

    // ===========================
    // PODACI ZA IZVJEŠTAJE
    // ===========================

    // Dnevni izvještaj: broj rezervacija po tipu vozila za određeni dan
    public function dailyVehicleReservationsByType($date)
    {
        return DB::table('reservations')
            ->join('vehicle_types', 'reservations.vehicle_type_id', '=', 'vehicle_types.id')
            ->select('vehicle_types.id as vehicle_type_id', 'vehicle_types.description_vehicle as tip_vozila', DB::raw('COUNT(*) as broj_rezervacija'))
            ->whereDate('reservation_date', $date)
            ->groupBy('vehicle_types.id', 'vehicle_types.description_vehicle')
            ->get();
    }

    // Mjesečni izvještaj: broj rezervacija po tipu vozila za određeni mjesec i godinu
    public function monthlyVehicleReservationsByType($month, $year)
    {
        return Reservation::whereYear('reservation_date', $year)
            ->whereMonth('reservation_date', $month)
            ->join('vehicle_types', 'reservations.vehicle_type_id', '=', 'vehicle_types.id')
            ->select('vehicle_types.id as vehicle_type_id', 'vehicle_types.description_vehicle as tip_vozila', DB::raw('COUNT(*) as broj_rezervacija'))
            ->groupBy('vehicle_types.id', 'vehicle_types.description_vehicle')
            ->get();
    }

    // Godišnji izvještaj: broj rezervacija po tipu vozila za određenu godinu
    public function yearlyVehicleReservationsByType($year)
    {
        return Reservation::whereYear('reservation_date', $year)
            ->join('vehicle_types', 'reservations.vehicle_type_id', '=', 'vehicle_types.id')
            ->select('vehicle_types.id as vehicle_type_id', 'vehicle_types.description_vehicle as tip_vozila', DB::raw('COUNT(*) as broj_rezervacija'))
            ->groupBy('vehicle_types.id', 'vehicle_types.description_vehicle')
            ->get();
    }

    // Dnevni finansijski izvještaj - zbir prihoda za određeni dan
    public function dailyFinancialReport($date)
    {
        return DB::table('reservations')
            ->join('vehicle_types', 'reservations.vehicle_type_id', '=', 'vehicle_types.id')
            ->whereDate('reservation_date', $date)
            ->where('status', 'paid')
            ->sum('vehicle_types.price');
    }

    // Mjesečni finansijski izvještaj - zbir prihoda za određeni mjesec i godinu
    public function monthlyFinancialReport($month, $year)
    {
        return DB::table('reservations')
            ->join('vehicle_types', 'reservations.vehicle_type_id', '=', 'vehicle_types.id')
            ->whereYear('reservation_date', $year)
            ->whereMonth('reservation_date', $month)
            ->where('status', 'paid')
            ->sum('vehicle_types.price');
    }

    // Godišnji finansijski izvještaj - zbir prihoda za određenu godinu
    public function yearlyFinancialReport($year)
    {
        return DB::table('reservations')
            ->join('vehicle_types', 'reservations.vehicle_type_id', '=', 'vehicle_types.id')
            ->whereYear('reservation_date', $year)
            ->where('status', 'paid')
            ->sum('vehicle_types.price');
    }

    /**
     * Zbir po mjesecima za finansijski godišnji izvještaj.
     * Vraća array: [1 => zbir_za_januar, 2 => zbir_za_februar, ... 12 => zbir_za_decembar]
     */
    public function yearlyFinancePerMonth($year)
    {
        $results = Reservation::whereYear('reservation_date', $year)
            ->join('vehicle_types', 'reservations.vehicle_type_id', '=', 'vehicle_types.id')
            ->selectRaw('MONTH(reservation_date) as mjesec, SUM(vehicle_types.price) as prihod')
            ->groupBy('mjesec')
            ->orderBy('mjesec')
            ->get();

        // Formiraj kolekciju za svih 12 mjeseci
        $collection = collect();
        for ($i = 1; $i <= 12; $i++) {
            $row = $results->firstWhere('mjesec', $i);
            $collection->push([
                'mjesec' => $i,
                'prihod' => $row ? (float)$row->prihod : 0
            ]);
        }
        return $collection;
    }

    // ===========================
    // SLANJE IZVJEŠTAJA PO TIPU VOZILA
    // ===========================

    // Šalje dnevni izvještaj o rezervacijama po tipu vozila
    public function sendDailyVehicleTypeReport($date)
    {
        $data = $this->dailyVehicleReservationsByType($date);
        // Uvek šalji izveštaj, čak i ako nema rezervacija
        Mail::to($this->getReportEmails())
            ->send(new DailyVehicleReservationReportMail($data, $date));
    }

    // Šalje mjesečni izvještaj o rezervacijama po tipu vozila
    public function sendMonthlyVehicleTypeReport($month, $year)
    {
        $data = $this->monthlyVehicleReservationsByType($month, $year);
        // Uvek šalji izveštaj, čak i ako nema rezervacija
        Mail::to($this->getReportEmails())
            ->send(new MonthlyVehicleReservationReportMail($month, $year, $data));
    }

    // Šalje godišnji izvještaj o rezervacijama po tipu vozila
    public function sendYearlyVehicleTypeReport($year)
    {
        $data = $this->yearlyVehicleReservationsByType($year);
        // Uvek šalji izveštaj, čak i ako nema rezervacija
        Mail::to($this->getReportEmails())
            ->send(new YearlyVehicleReservationReportMail($year, $data));
    }

    // ===========================
    // SLANJE FINANSIJSKIH IZVJEŠTAJA
    // ===========================

    // Šalje dnevni finansijski izvještaj
    public function sendDailyFinancialReport($date)
    {
        $total = $this->dailyFinancialReport($date);
        $count = $this->dailyCount($date);
        Mail::to($this->getReportEmails())
            ->send(new DailyFinanceReportMail($date, $total, $count));
    }

    // Šalje mjesečni finansijski izvještaj
    public function sendMonthlyFinancialReport($month, $year)
    {
        $total = $this->monthlyFinancialReport($month, $year);
        $count = $this->monthlyCount($month, $year);
        Mail::to($this->getReportEmails())
            ->send(new MonthlyFinanceReportMail($month, $year, $total, $count));
    }

    // Šalje godišnji finansijski izvještaj
    public function sendYearlyFinancialReport($year)
    {
        $financePerMonth = $this->yearlyFinancePerMonth($year);
        $totalFinance = $this->yearlyFinancialReport($year);
        $totalCount = $this->yearlyCount($year);
        Mail::to($this->getReportEmails())
            ->send(new YearlyFinanceReportMail($year, $financePerMonth, $totalFinance, $totalCount));
    }

    // ===========================
    // DOWNLOAD IZVJEŠTAJA (opciono)
    // ===========================

    // Vrati PDF izvještaj po tipu vozila za preuzimanje (za admin panel)
    public function downloadVehicleTypeReport($periodType, $params)
    {
        switch ($periodType) {
            case 'daily':
                $data = $this->dailyVehicleReservationsByType($params['date']);
                $view = 'reports.vehicle_type_daily';
                $variables = [
                    'reservationsByType' => $data,
                    'date' => $params['date']
                ];
                $filename = 'izvjestaj_po_tipovima_vozila_'.$params['date'].'.pdf';
                break;
            case 'monthly':
                $data = $this->monthlyVehicleReservationsByType($params['month'], $params['year']);
                $view = 'reports.vehicle_type_monthly';
                $variables = [
                    'reservationsByType' => $data,
                    'month' => $params['month'],
                    'year' => $params['year']
                ];
                $filename = 'izvjestaj_po_tipovima_vozila_'.$params['month'].'_'.$params['year'].'.pdf';
                break;
            case 'yearly':
                $data = $this->yearlyVehicleReservationsByType($params['year']);
                $view = 'reports.vehicle_type_yearly';
                $variables = [
                    'reservationsByType' => $data,
                    'year' => $params['year']
                ];
                $filename = 'izvjestaj_po_tipovima_vozila_'.$params['year'].'.pdf';
                break;
            default:
                throw new \InvalidArgumentException('Nepoznat period');
        }

        $pdf = Pdf::loadView($view, $variables);
        return $pdf->download($filename);
    }



    // Vrati PDF finansijski izvještaj za preuzimanje (za admin panel)
    public function downloadFinancialReport($periodType, $params)
    {
        switch ($periodType) {
            case 'daily':
                $total = $this->dailyFinancialReport($params['date']);
                $count = $this->dailyCount($params['date']);
                $view = 'reports.financial_daily';
                $variables = [
                    'total' => $total,
                    'count' => $count,
                    'date' => $params['date']
                ];
                $filename = 'finansijski_izvjestaj_'.$params['date'].'.pdf';
                break;
            case 'monthly':
                $total = $this->monthlyFinancialReport($params['month'], $params['year']);
                $view = 'reports.financial_monthly';
                $variables = [
                    'total' => $total,
                    'month' => $params['month'],
                    'year' => $params['year']
                ];
                $filename = 'finansijski_izvjestaj_'.$params['month'].'_'.$params['year'].'.pdf';
                break;
            case 'yearly':
                $total = $this->yearlyFinancialReport($params['year']);
                $financePerMonth = $this->yearlyFinancePerMonth($params['year']);
                $view = 'reports.financial_yearly';
                $variables = [
                    'totalFinance' => $total,
                    'financeData' => $financePerMonth,
                    'year' => $params['year']
                ];
                $filename = 'finansijski_izvjestaj_'.$params['year'].'.pdf';
                break;
            default:
                throw new \InvalidArgumentException('Nepoznat period');
        }

        $pdf = Pdf::loadView($view, $variables);
        return $pdf->download($filename);
    }

    // Broj rezervacija za dati dan (samo paid)
    public function dailyCount($date)
    {
        return DB::table('reservations')
            ->whereDate('reservation_date', $date)
            ->where('status', 'paid')
            ->count();
    }

    // (Opcionalno) Broj rezervacija za mjesec (samo paid)
    public function monthlyCount($month, $year)
    {
        return DB::table('reservations')
            ->whereYear('reservation_date', $year)
            ->whereMonth('reservation_date', $month)
            ->where('status', 'paid')
            ->count();
    }

    // (Opcionalno) Broj rezervacija za godinu (samo paid)
    public function yearlyCount($year)
    {
        return DB::table('reservations')
            ->whereYear('reservation_date', $year)
            ->where('status', 'paid')
            ->count();
    }
}