<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReportService;
use App\Mail\DailyFinanceReportMail;
use App\Mail\DailyVehicleReservationReportMail;
use App\Mail\MonthlyFinanceReportMail;
use App\Mail\MonthlyVehicleReservationReportMail;
use App\Mail\YearlyFinanceReportMail;
use App\Mail\YearlyVehicleReservationReportMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    protected function getReportEmails()
    {
        return DB::table('report_emails')->where('name', 'report_email')->pluck('value')->toArray();
    }

    public function sendDailyFinance(Request $request, ReportService $service)
    {
        $date = $request->get('date', now()->subDay()->format('Y-m-d'));

        // Dodaj "paid" i "free" statistiku
        $stats = $service->dailyReservationStats($date);

        $pdf = Pdf::loadView('reports.daily_finance_report_pdf', [
            'paid_total' => $stats['paid_total'],
            'paid_count' => $stats['paid_count'],
            'free_count' => $stats['free_count'],
            'date' => $date,
        ])->setPaper('a4', 'portrait')->setOptions([
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

        // Mail slanje (primjer, koristi po potrebi)
        // Mail::to($this->getReportEmails())->send(new DailyFinanceReportMail($pdf->output()));

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="dnevni_finansijski_izvjestaj_' . $date . '.pdf"');
    }

    public function sendDailyVehicleReservations(Request $request, ReportService $service)
    {
        $date = $request->get('date', Carbon::yesterday()->toDateString());
        $reservationsByType = $service->dailyVehicleReservationsByType($date);

        $pdf = Pdf::loadView('reports.daily_vehicle_reservation_report_pdf', [
            'date' => $date,
            'reservationsByType' => $reservationsByType,
        ])->setPaper('a4', 'portrait')->setOptions([
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

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="dnevni_izvjestaj_rezervacije_po_voznom_parku_' . $date . '.pdf"');
    }

    public function sendMonthlyFinance(Request $request, ReportService $service)
    {
        $month = $request->get('month', Carbon::now()->subMonth()->month);
        $year = $request->get('year', Carbon::now()->subMonth()->year);
        $month = (int)$month;

        // Dodaj "paid" i "free" statistiku
        $stats = $service->monthlyReservationStats($month, $year);

        $pdf = Pdf::loadView('reports.monthly_finance_report_pdf', [
            'paid_total' => $stats['paid_total'],
            'paid_count' => $stats['paid_count'],
            'free_count' => $stats['free_count'],
            'month' => $month,
            'year' => $year,
        ])->setPaper('a4', 'portrait')->setOptions([
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

        // Mail slanje (primjer, koristi po potrebi)
        // Mail::to($this->getReportEmails())->send(new MonthlyFinanceReportMail($pdf->output()));

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="mjesecni_finansijski_izvjestaj_' . $month . '_' . $year . '.pdf"');
    }

    public function sendMonthlyVehicleReservations(Request $request, ReportService $service)
    {
        $month = $request->get('month', Carbon::now()->subMonth()->month);
        $year = $request->get('year', Carbon::now()->subMonth()->year);
        $month = (int)$month;
        $reservationsByType = $service->monthlyVehicleReservationsByType($month, $year);

        $pdf = Pdf::loadView('reports.monthly_vehicle_reservation_report_pdf', [
            'month' => $month,
            'year' => $year,
            'reservationsByType' => $reservationsByType,
        ])->setPaper('a4', 'portrait')->setOptions([
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

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="mjesecni_izvjestaj_rezervacije_po_voznom_parku_' . $month . '_' . $year . '.pdf"');
    }

    public function sendYearlyFinance(Request $request, ReportService $service)
    {
        $year = $request->get('year', Carbon::now()->subYear()->year);

        // Dodaj "paid" i "free" statistiku
        $stats = $service->yearlyReservationStats($year);
        $financePerMonth = $service->yearlyFinancePerMonth($year);

        $pdf = Pdf::loadView('reports.yearly_finance_report_pdf', [
            'paid_total' => $stats['paid_total'],
            'paid_count' => $stats['paid_count'],
            'free_count' => $stats['free_count'],
            'financeData' => $financePerMonth,
            'year' => $year,
        ])->setPaper('a4', 'portrait')->setOptions([
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

        // Mail slanje (primjer, koristi po potrebi)
        // Mail::to($this->getReportEmails())->send(new YearlyFinanceReportMail($pdf->output()));

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="godisnji_finansijski_izvjestaj_' . $year . '.pdf"');
    }

    public function sendYearlyVehicleReservations(Request $request, ReportService $service)
    {
        $year = $request->get('year', Carbon::now()->subYear()->year);
        $reservationsByType = $service->yearlyVehicleReservationsByType($year);

        $pdf = Pdf::loadView('reports.yearly_vehicle_reservation_report_pdf', [
            'year' => $year,
            'reservationsByType' => $reservationsByType,
        ])->setPaper('a4', 'portrait')->setOptions([
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

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="godisnji_izvjestaj_rezervacije_po_voznom_parku_' . $year . '.pdf"');
    }
}