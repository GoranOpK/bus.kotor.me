<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class YearlyFinanceReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $year;
    public $financePerMonth;
    public $totalFinance;
    public $totalCount;

    /**
     * Konstruktor - prosljeđuje podatke za izvještaj
     */
    public function __construct($year, $financePerMonth, $totalFinance, $totalCount = 0)
    {
        $this->year = $year;
        $this->financePerMonth = $financePerMonth;
        $this->totalFinance = $totalFinance;
        $this->totalCount = $totalCount;
    }

    /**
     * Priprema email sa godišnjim finansijskim izvještajem u pdf-u
     */
    public function build()
    {
        $pdf = Pdf::loadView('reports.yearly_finance_report_pdf', [
            'year' => $this->year,
            'financeData' => $this->financePerMonth,
            'totalFinance' => $this->totalFinance,
            'totalCount' => $this->totalCount,
        ]);

        return $this->subject('Godišnji finansijski izvještaj')
            ->view('emails.blank')
            ->attachData(
                $pdf->output(),
                'godisnji_finansijski_izvjestaj.pdf',
                ['mime' => 'application/pdf']
            );
    }
}