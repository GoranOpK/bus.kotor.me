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
        $month = (int)$month; // Osiguraj integer!
        return DB::table('reservations')
            ->join('vehicle_types', 'reservations.vehicle_type_id', '=', 'vehicle_types.id')
            ->select('vehicle_types.id as vehicle_type_id', 'vehicle_types.description_vehicle as tip_vozila', DB::raw('COUNT(*) as broj_rezervacija'))
<<<<<<< HEAD
            ->whereYear('reservation_date', $year)
            ->whereMonth('reservation_date', $month)
=======
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
            ->groupBy('vehicle_types.id', 'vehicle_types.description_vehicle')
            ->get();
    }

    // Godišnji izvještaj: broj rezervacija po tipu vozila za određenu godinu
    public function yearlyVehicleReservationsByType($year)
    {
        return DB::table('reservations')
            ->join('vehicle_types', 'reservations.vehicle_type_id', '=', 'vehicle_types.id')
            ->select('vehicle_types.id as vehicle_type_id', 'vehicle_types.description_vehicle as tip_vozila', DB::raw('COUNT(*) as broj_rezervacija'))
<<<<<<< HEAD
            ->whereYear('reservation_date', $year)
=======
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
            ->groupBy('vehicle_types.id', 'vehicle_types.description_vehicle')
            ->get();
    }

<<<<<<< HEAD
    // Dnevni finansijski izvještaj - zbir prihoda za određeni dan (fiksna cijena po tipu vozila)
=======
    // Dnevni finansijski izvještaj - zbir prihoda za određeni dan
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
    public function dailyFinancialReport($date)
    {
        return DB::table('reservations')
            ->join('vehicle_types', 'reservations.vehicle_type_id', '=', 'vehicle_types.id')
            ->whereDate('reservation_date', $date)
<<<<<<< HEAD
            ->where('reservations.status', 'paid')
            ->sum('vehicle_types.price');
    }

    // Mjesečni finansijski izvještaj - zbir prihoda za određeni mjesec i godinu (fiksna cijena po tipu vozila)
    public function monthlyFinancialReport($month, $year)
    {
        $month = (int)$month;
=======
            ->where('status', 'paid')
            ->sum('vehicle_types.price');
    }

    // Mjesečni finansijski izvještaj - zbir prihoda za određeni mjesec i godinu
    public function monthlyFinancialReport($month, $year)
    {
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
        return DB::table('reservations')
            ->join('vehicle_types', 'reservations.vehicle_type_id', '=', 'vehicle_types.id')
            ->whereYear('reservation_date', $year)
            ->whereMonth('reservation_date', $month)
<<<<<<< HEAD
            ->where('reservations.status', 'paid')
            ->sum('vehicle_types.price');
    }

    // Godišnji finansijski izvještaj - zbir prihoda za određenu godinu (fiksna cijena po tipu vozila)
=======
            ->where('status', 'paid')
            ->sum('vehicle_types.price');
    }

    // Godišnji finansijski izvještaj - zbir prihoda za određenu godinu
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
    public function yearlyFinancialReport($year)
    {
        return DB::table('reservations')
            ->join('vehicle_types', 'reservations.vehicle_type_id', '=', 'vehicle_types.id')
            ->whereYear('reservation_date', $year)
<<<<<<< HEAD
            ->where('reservations.status', 'paid')
=======
            ->where('status', 'paid')
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
            ->sum('vehicle_types.price');
    }

    /**
     * Zbir po mjesecima za finansijski godišnji izvještaj.
     * Vraća array: [1 => zbir_za_januar, 2 => zbir_za_februar, ... 12 => zbir_za_decembar]
     */
    public function yearlyFinancePerMonth($year)
    {
<<<<<<< HEAD
        $results = DB::table('reservations')
            ->join('vehicle_types', 'reservations.vehicle_type_id', '=', 'vehicle_types.id')
            ->selectRaw('MONTH(reservation_date) as mjesec, SUM(vehicle_types.price) as prihod')
            ->whereYear('reservation_date', $year)
            ->where('reservations.status', 'paid')
=======
        $results = Reservation::whereYear('reservation_date', $year)
            ->join('vehicle_types', 'reservations.vehicle_type_id', '=', 'vehicle_types.id')
            ->selectRaw('MONTH(reservation_date) as mjesec, SUM(vehicle_types.price) as prihod')
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
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
<<<<<<< HEAD
    // FREE & PAID STATISTIKA
    // ===========================

    // Dnevna statistika: paid/free count & suma
    public function dailyReservationStats($date)
    {
        $paidCount = DB::table('reservations')
            ->whereDate('reservation_date', $date)
            ->where('status', 'paid')
            ->count();

        $paidTotal = DB::table('reservations')
            ->join('vehicle_types', 'reservations.vehicle_type_id', '=', 'vehicle_types.id')
            ->whereDate('reservation_date', $date)
            ->where('reservations.status', 'paid')
            ->sum('vehicle_types.price');

        $freeCount = DB::table('reservations')
            ->whereDate('reservation_date', $date)
            ->where('status', 'free')
            ->count();

        return [
            'paid_count' => $paidCount,
            'paid_total' => $paidTotal,
            'free_count' => $freeCount,
        ];
    }

    // Mjesečna statistika: paid/free count & suma
    public function monthlyReservationStats($month, $year)
    {
        $paidCount = DB::table('reservations')
            ->whereYear('reservation_date', $year)
            ->whereMonth('reservation_date', $month)
            ->where('status', 'paid')
            ->count();

        $paidTotal = DB::table('reservations')
            ->join('vehicle_types', 'reservations.vehicle_type_id', '=', 'vehicle_types.id')
            ->whereYear('reservation_date', $year)
            ->whereMonth('reservation_date', $month)
            ->where('reservations.status', 'paid')
            ->sum('vehicle_types.price');

        $freeCount = DB::table('reservations')
            ->whereYear('reservation_date', $year)
            ->whereMonth('reservation_date', $month)
            ->where('status', 'free')
            ->count();

        return [
            'paid_count' => $paidCount,
            'paid_total' => $paidTotal,
            'free_count' => $freeCount,
        ];
    }

    // Godišnja statistika: paid/free count & suma
    public function yearlyReservationStats($year)
    {
        $paidCount = DB::table('reservations')
            ->whereYear('reservation_date', $year)
            ->where('status', 'paid')
            ->count();

        $paidTotal = DB::table('reservations')
            ->join('vehicle_types', 'reservations.vehicle_type_id', '=', 'vehicle_types.id')
            ->whereYear('reservation_date', $year)
            ->where('reservations.status', 'paid')
            ->sum('vehicle_types.price');

        $freeCount = DB::table('reservations')
            ->whereYear('reservation_date', $year)
            ->where('status', 'free')
            ->count();

        return [
            'paid_count' => $paidCount,
            'paid_total' => $paidTotal,
            'free_count' => $freeCount,
        ];
    }

    // ===========================
    // SLANJE IZVJEŠTAJA PO TIPU VOZILA
    // ===========================

    public function sendDailyVehicleTypeReport($date)
    {
        $data = $this->dailyVehicleReservationsByType($date);
        Mail::to($this->getReportEmails())
            ->send(new DailyVehicleReservationReportMail($data, $date));
    }

    public function sendMonthlyVehicleTypeReport($month, $year)
    {
        $month = (int)$month;
        $data = $this->monthlyVehicleReservationsByType($month, $year);
        Mail::to($this->getReportEmails())
            ->send(new MonthlyVehicleReservationReportMail($month, $year, $data));
    }

    public function sendYearlyVehicleTypeReport($year)
    {
        $data = $this->yearlyVehicleReservationsByType($year);
        Mail::to($this->getReportEmails())
            ->send(new YearlyVehicleReservationReportMail($year, $data));
    }

    // ===========================
    // SLANJE FINANSIJSKIH IZVJEŠTAJA
    // ===========================

    // Slanje dnevnog finansijskog izvještaja sa paid/free statistikama
    public function sendDailyFinancialReport($date)
    {
        $stats = $this->dailyReservationStats($date);
        Mail::to($this->getReportEmails())
            ->send(new DailyFinanceReportMail(
                $date,
                $stats['paid_total'],
                $stats['paid_count'],
                $stats['free_count'],
                'Dnevni finansijski izvještaj - Kotor Bus'
            ));
    }

    // Slanje mjesečnog finansijskog izvještaja sa paid/free statistikama
    public function sendMonthlyFinancialReport($month, $year)
    {
        $month = (int)$month;
        $stats = $this->monthlyReservationStats($month, $year);
        Mail::to($this->getReportEmails())
            ->send(new MonthlyFinanceReportMail(
                $month,
                $year,
                $stats['paid_total'],
                $stats['paid_count'],
                $stats['free_count'],
                'Mjesečni finansijski izvještaj - Kotor Bus'
            ));
    }

    // Slanje godišnjeg finansijskog izvještaja sa paid/free statistikama
    public function sendYearlyFinancialReport($year)
    {
        $stats = $this->yearlyReservationStats($year);
        $financeData = $this->yearlyFinancePerMonth($year);
        Mail::to($this->getReportEmails())
            ->send(new YearlyFinanceReportMail(
                $year,
                $financeData,
                $stats['paid_total'],
                $stats['paid_count'],
                $stats['free_count'],
                'Godišnji finansijski izvještaj - Kotor Bus'
            ));
    }

    // ===========================
    // DOWNLOAD IZVJEŠTAJA (opciono)
    // ===========================

=======
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
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
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
                $month = (int)$params['month'];
                $data = $this->monthlyVehicleReservationsByType($month, $params['year']);
                $view = 'reports.vehicle_type_monthly';
                $variables = [
                    'reservationsByType' => $data,
<<<<<<< HEAD
                    'month' => $month,
                    'year' => $params['year']
                ];
                $filename = 'izvjestaj_po_tipovima_vozila_'.$month.'_'.$params['year'].'.pdf';
=======
                    'month' => $params['month'],
                    'year' => $params['year']
                ];
                $filename = 'izvjestaj_po_tipovima_vozila_'.$params['month'].'_'.$params['year'].'.pdf';
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
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

<<<<<<< HEAD
        $pdf = Pdf::loadView($view, $variables)->setPaper('a4', 'portrait')->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => false,
            'isJavascriptEnabled' => false,
            'defaultMediaType' => 'screen',
            'isFontSubsettingEnabled' => false,
            'dpi' => 96,
            'fontHeightRatio' => 1.1,
            'defaultEncoding' => 'UTF-8',
        ]);
        return $pdf->download($filename);
    }

=======
        $pdf = Pdf::loadView($view, $variables);
        return $pdf->download($filename);
    }



>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
    // Vrati PDF finansijski izvještaj za preuzimanje (za admin panel)
    public function downloadFinancialReport($periodType, $params)
    {
        switch ($periodType) {
            case 'daily':
<<<<<<< HEAD
                $stats = $this->dailyReservationStats($params['date']);
                $view = 'reports.financial_daily';
                $variables = [
                    'paid_total' => $stats['paid_total'],
                    'paid_count' => $stats['paid_count'],
                    'free_count' => $stats['free_count'],
=======
                $total = $this->dailyFinancialReport($params['date']);
                $count = $this->dailyCount($params['date']);
                $view = 'reports.financial_daily';
                $variables = [
                    'total' => $total,
                    'count' => $count,
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
                    'date' => $params['date']
                ];
                $filename = 'finansijski_izvjestaj_'.$params['date'].'.pdf';
                break;
            case 'monthly':
                $month = (int)$params['month'];
                $stats = $this->monthlyReservationStats($month, $params['year']);
                $view = 'reports.financial_monthly';
                $variables = [
<<<<<<< HEAD
                    'paid_total' => $stats['paid_total'],
                    'paid_count' => $stats['paid_count'],
                    'free_count' => $stats['free_count'],
                    'month' => $month,
                    'year' => $params['year']
                ];
                $filename = 'finansijski_izvjestaj_'.$month.'_'.$params['year'].'.pdf';
                break;
            case 'yearly':
                $stats = $this->yearlyReservationStats($params['year']);
                $financePerMonth = $this->yearlyFinancePerMonth($params['year']);
                $view = 'reports.financial_yearly';
                $variables = [
                    'paid_total' => $stats['paid_total'],
                    'paid_count' => $stats['paid_count'],
                    'free_count' => $stats['free_count'],
=======
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
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
                    'financeData' => $financePerMonth,
                    'year' => $params['year']
                ];
                $filename = 'finansijski_izvjestaj_'.$params['year'].'.pdf';
                break;
            default:
                throw new \InvalidArgumentException('Nepoznat period');
        }

<<<<<<< HEAD
        $pdf = Pdf::loadView($view, $variables)->setPaper('a4', 'portrait')->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isRemoteEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => false,
            'isJavascriptEnabled' => false,
            'defaultMediaType' => 'screen',
            'isFontSubsettingEnabled' => false,
            'dpi' => 96,
            'fontHeightRatio' => 1.1,
            'defaultEncoding' => 'UTF-8',
        ]);
=======
        $pdf = Pdf::loadView($view, $variables);
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
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

<<<<<<< HEAD
    // Broj rezervacija za mjesec (samo paid)
    public function monthlyCount($month, $year)
    {
        $month = (int)$month;
=======
    // (Opcionalno) Broj rezervacija za mjesec (samo paid)
    public function monthlyCount($month, $year)
    {
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
        return DB::table('reservations')
            ->whereYear('reservation_date', $year)
            ->whereMonth('reservation_date', $month)
            ->where('status', 'paid')
            ->count();
    }

<<<<<<< HEAD
    // Broj rezervacija za godinu (samo paid)
=======
    // (Opcionalno) Broj rezervacija za godinu (samo paid)
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
    public function yearlyCount($year)
    {
        return DB::table('reservations')
            ->whereYear('reservation_date', $year)
            ->where('status', 'paid')
            ->count();
    }
}