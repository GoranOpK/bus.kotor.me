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
<<<<<<< HEAD
    public $totalCount;
=======
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb

    /**
     * Konstruktor - prosljeđuje podatke za izvještaj
     */
<<<<<<< HEAD
    public function __construct($year, $financePerMonth, $totalFinance, $totalCount = 0)
=======
    public function __construct($year, $financePerMonth, $totalFinance)
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
    {
        $this->year = $year;
        $this->financePerMonth = $financePerMonth;
        $this->totalFinance = $totalFinance;
<<<<<<< HEAD
        $this->totalCount = $totalCount;
=======
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
    }

    /**
     * Priprema email sa godišnjim finansijskim izvještajem u pdf-u
     */
    public function build()
    {
<<<<<<< HEAD
=======
        // Prosleđujemo podatke kao array sa ključevima koje koristiš u blade-u
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
        $pdf = Pdf::loadView('reports.yearly_finance_report_pdf', [
            'year' => $this->year,
            'financeData' => $this->financePerMonth,
            'totalFinance' => $this->totalFinance,
<<<<<<< HEAD
            'totalCount' => $this->totalCount,
        ]);

        return $this->subject('Godišnji finansijski izvještaj')
            ->view('emails.blank')
=======
        ]);

        return $this->subject('Godišnji finansijski izvještaj')
            ->text('emails.empty') // obavezno napravi prazan view emails/empty.blade.php
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
            ->attachData(
                $pdf->output(),
                'godisnji_finansijski_izvjestaj.pdf',
                ['mime' => 'application/pdf']
            );
    }
}