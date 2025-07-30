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

class ReportController extends Controller
{
<<<<<<< HEAD
    protected function getReportEmails()
=======
    protected $emails = [
        'prihodi@kotor.me',
        'mirjana.grbovic@kotor.me',
        'informatika@kotor.me',
    ];

    public function sendDailyFinance(ReportService $service)
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
    {
        return DB::table('report_emails')->where('name', 'report_email')->pluck('value')->toArray();
    }

    public function sendDailyFinance(Request $request, ReportService $service)
    {
        $date = $request->get('date', now()->subDay()->format('Y-m-d'));
        $finance = $service->dailyFinancialReport($date);
        $count = $service->dailyCount($date);

<<<<<<< HEAD
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.daily_finance_report_pdf', [
            'total' => $finance,
            'count' => $count,
            'date' => $date,
        ]);

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="dnevni_finansijski_izvjestaj_' . $date . '.pdf"');
=======
        foreach ($this->emails as $email) {
            Mail::to($email)->send(new DailyFinanceReportMail($date, $finance, $count));
        }
        return response()->json(['status' => 'Dnevni finansijski izvještaj je poslat!']);
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
    }

    public function sendDailyVehicleReservations(Request $request, ReportService $service)
    {
        $date = $request->get('date', Carbon::yesterday()->toDateString());
        $reservationsByType = $service->dailyVehicleReservationsByType($date);

<<<<<<< HEAD
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.daily_vehicle_reservation_report_pdf', [
            'date' => $date,
            'reservationsByType' => $reservationsByType,
        ]);

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="dnevni_izvjestaj_rezervacije_po_voznom_parku_' . $date . '.pdf"');
=======
        foreach ($this->emails as $email) {
            Mail::to($email)->send(new DailyVehicleReservationReportMail($reservationsByType, $date));
        }
        return response()->json(['status' => 'Dnevni izvještaj po tipu vozila je poslat!']);
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
    }

    public function sendMonthlyFinance(Request $request, ReportService $service)
    {
        $month = $request->get('month', Carbon::now()->subMonth()->month);
        $year = $request->get('year', Carbon::now()->subMonth()->year);
        $finance = $service->monthlyFinancialReport($month, $year);
<<<<<<< HEAD
        $count = $service->monthlyCount($month, $year);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.monthly_finance_report_pdf', [
            'total' => $finance,
            'count' => $count,
            'month' => $month,
            'year' => $year,
        ]);

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="mjesecni_finansijski_izvjestaj_' . $month . '_' . $year . '.pdf"');
=======

        foreach ($this->emails as $email) {
            Mail::to($email)->send(new MonthlyFinanceReportMail($month, $year, $finance));
        }
        return response()->json(['status' => 'Mjesečni finansijski izvještaj je poslat!']);
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
    }

    public function sendMonthlyVehicleReservations(Request $request, ReportService $service)
    {
<<<<<<< HEAD
        $month = $request->get('month', Carbon::now()->subMonth()->month);
        $year = $request->get('year', Carbon::now()->subMonth()->year);
        $reservationsByType = $service->monthlyVehicleReservationsByType($month, $year);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.monthly_vehicle_reservation_report_pdf', [
            'month' => $month,
            'year' => $year,
            'reservationsByType' => $reservationsByType,
        ]);

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="mjesecni_izvjestaj_rezervacije_po_voznom_parku_' . $month . '_' . $year . '.pdf"');
=======
        $date = Carbon::now()->subMonth();
        $month = $date->month;
        $year = $date->year;
        $reservationsByType = $service->monthlyVehicleReservationsByType($month, $year);

        foreach ($this->emails as $email) {
            Mail::to($email)->send(new MonthlyVehicleReservationReportMail($month, $year, $reservationsByType));
        }
        return response()->json(['status' => 'Mjesečni izvještaj po tipu vozila je poslat!']);
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
    }

    public function sendYearlyFinance(Request $request, ReportService $service)
    {
<<<<<<< HEAD
        $year = $request->get('year', Carbon::now()->subYear()->year);
        $financePerMonth = $service->yearlyFinancePerMonth($year);
        $totalFinance = $service->yearlyFinancialReport($year);
        $totalCount = $service->yearlyCount($year);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.yearly_finance_report_pdf', [
            'totalFinance' => $totalFinance,
            'financeData' => $financePerMonth,
            'totalCount' => $totalCount,
            'year' => $year,
        ]);

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="godisnji_finansijski_izvjestaj_' . $year . '.pdf"');
=======
        $year = Carbon::now()->subYear()->year;
        $financePerMonth = $service->yearlyFinancialReport($year);
        $totalFinance = is_iterable($financePerMonth) ? collect($financePerMonth)->sum('prihod') : 0;

        foreach ($this->emails as $email) {
            Mail::to($email)->send(new YearlyFinanceReportMail($year, $financePerMonth, $totalFinance));
        }
        return response()->json(['status' => 'Godišnji finansijski izvještaj je poslat!']);
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
    }

    public function sendYearlyVehicleReservations(Request $request, ReportService $service)
    {
        $year = $request->get('year', Carbon::now()->subYear()->year);
        $reservationsByType = $service->yearlyVehicleReservationsByType($year);

<<<<<<< HEAD
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.yearly_vehicle_reservation_report_pdf', [
            'year' => $year,
            'reservationsByType' => $reservationsByType,
        ]);

        return response($pdf->output())
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="godisnji_izvjestaj_rezervacije_po_voznom_parku_' . $year . '.pdf"');
=======
        foreach ($this->emails as $email) {
            Mail::to($email)->send(new YearlyVehicleReservationReportMail($year, $reservationsByType));
        }
        return response()->json(['status' => 'Godišnji izvještaj po tipu vozila je poslat!']);
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
    }
}