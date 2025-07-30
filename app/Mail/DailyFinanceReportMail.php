<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class DailyFinanceReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $pdf;
    public $periodLabel;
    public $periodString;
    public $total;
    public $count;
    public $date;

    /**
     * Konstruktor prima podatke za izvještaj.
     *
     * @param string $date Datum izvještaja
     * @param float $total Finansijski zbir
     * @param int $count  Broj rezervacija
     */
    public function __construct($date, $total, $count)
    {
        $this->date = $date;
        $this->total = $total;
        $this->count = $count;
<<<<<<< HEAD
=======

        $this->periodLabel = 'Dnevni finansijski izvještaj';
        $this->periodString = $date;

        // PDF se generiše ovdje, da bi mogao biti attachovan direktno
        $this->pdf = Pdf::loadView('reports.daily_finance_report_pdf', [
            'total' => $this->total,
            'count' => $this->count,
            'date' => $this->date,
        ]);
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
    }

    public function build()
    {
<<<<<<< HEAD
        $pdf = Pdf::loadView('reports.daily_finance_report_pdf', [
            'total' => $this->total,
            'count' => $this->count,
            'date' => $this->date,
        ]);
        return $this->subject('Dnevni finansijski izvještaj - Kotor Bus')
            ->view('emails.blank')
            ->attachData($pdf->output(), 'daily-finance-report.pdf');
=======
        return $this->subject($this->periodLabel)
            ->view('emails.daily_finance_report_body')
            ->with([
                'total' => $this->total,
                'count' => $this->count,
                'date' => $this->date,
            ])
            ->attachData($this->pdf->output(), 'daily-finance-report.pdf');
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
    }
}