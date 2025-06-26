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

    /**
     * Konstruktor
     */
    public function __construct($month, $year, $finance)
    {
        $this->month = $month;
        $this->year = $year;
        $this->finance = $finance;
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
        ]);

        return $this->subject('Mjesečni finansijski izvještaj')
            ->text('emails.empty')
            ->attachData(
                $pdf->output(),
                'mjesecni_finansijski_izvjestaj.pdf',
                ['mime' => 'application/pdf']
            );
    }
}