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

    /**
     * Konstruktor - prosljeđuje podatke za izvještaj
     */
    public function __construct($year, $financePerMonth, $totalFinance)
    {
        $this->year = $year;
        $this->financePerMonth = $financePerMonth;
        $this->totalFinance = $totalFinance;
    }

    /**
     * Priprema email sa godišnjim finansijskim izvještajem u pdf-u
     */
    public function build()
    {
        // Prosleđujemo podatke kao array sa ključevima koje koristiš u blade-u
        $pdf = Pdf::loadView('reports.yearly_finance_report_pdf', [
            'year' => $this->year,
            'financeData' => $this->financePerMonth,
            'totalFinance' => $this->totalFinance,
        ]);

        return $this->subject('Godišnji finansijski izvještaj')
            ->text('emails.empty') // obavezno napravi prazan view emails/empty.blade.php
            ->attachData(
                $pdf->output(),
                'godisnji_finansijski_izvjestaj.pdf',
                ['mime' => 'application/pdf']
            );
    }
}