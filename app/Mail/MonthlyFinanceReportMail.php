<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class MonthlyFinanceReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $finance;
    public $month;
    public $year;
    public $count;

    /**
     * Konstruktor
     */
    public function __construct($month, $year, $finance, $count = 0)
    {
        $this->month = $month;
        $this->year = $year;
        $this->finance = $finance;
        $this->count = $count;
    }

    /**
     * Priprema email sa mjesečnim finansijskim izvještajem u pdf-u
     */
    public function build()
    {
        $pdf = Pdf::loadView('reports.monthly_finance_report_pdf', [
            'month' => $this->month,
            'year' => $this->year,
            'finance' => $this->finance,
            'count' => $this->count,
        ]);

        return $this->subject('Mjesečni finansijski izvještaj')
            ->view('emails.blank')
            ->attachData(
                $pdf->output(),
                'mjesecni_finansijski_izvjestaj.pdf',
                ['mime' => 'application/pdf']
            );
    }
}