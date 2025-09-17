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
    }

    public function build()
    {
        $pdf = Pdf::loadView('reports.daily_finance_report_pdf', [
            'total' => $this->total,
            'count' => $this->count,
            'date' => $this->date,
        ]);
        return $this->subject('Dnevni finansijski izvještaj - Kotor Bus')
            ->view('emails.blank')
            ->attachData($pdf->output(), 'daily-finance-report.pdf');
    }
}