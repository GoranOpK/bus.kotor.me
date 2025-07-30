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
<<<<<<< HEAD
    public $count;
=======
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb

    /**
     * Konstruktor
     */
<<<<<<< HEAD
    public function __construct($month, $year, $finance, $count = 0)
=======
    public function __construct($month, $year, $finance)
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
    {
        $this->month = $month;
        $this->year = $year;
        $this->finance = $finance;
<<<<<<< HEAD
        $this->count = $count;
=======
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
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
<<<<<<< HEAD
            'count' => $this->count,
=======
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
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