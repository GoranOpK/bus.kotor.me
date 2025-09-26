<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Mail klasa za dnevni finansijski izvještaj
 * Sada prima paid_total, paid_count, free_count (novi parametri)
 */
class DailyFinanceReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $date;           // Datum izvještaja
    public $paid_total;     // Ukupan prihod od plaćenih rezervacija
    public $paid_count;     // Broj plaćenih rezervacija
    public $free_count;     // Broj besplatnih rezervacija
    public $title;          // Naslov izvještaja (opciono)

    /**
     * Konstruktor prima sve potrebne podatke za izvještaj
     *
     * @param string $date
     * @param float $paid_total
     * @param int $paid_count
     * @param int $free_count
     * @param string|null $title
     */
    public function __construct($date, $paid_total, $paid_count, $free_count, $title = null)
    {
        $this->date = $date;
        $this->paid_total = $paid_total;
        $this->paid_count = $paid_count;
        $this->free_count = $free_count;
        $this->title = $title ?? 'Dnevni finansijski izvještaj - Kotor Bus';
    }

    /**
     * Priprema email sa pdf-om
     */
    public function build()
    {
        // Pravi PDF sa novim parametrima
        $pdf = Pdf::loadView('reports.daily_finance_report_pdf', [
            'date' => $this->date,
            'paid_total' => $this->paid_total,
            'paid_count' => $this->paid_count,
            'free_count' => $this->free_count,
            'title' => $this->title,
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

        return $this->subject($this->title)
            ->view('emails.blank') // možeš promijeniti view po potrebi
            ->attachData($pdf->output(), 'daily-finance-report.pdf', ['mime' => 'application/pdf']);
    }
}