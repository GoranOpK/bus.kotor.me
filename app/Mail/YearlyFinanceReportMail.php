<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Mail klasa za godišnji finansijski izvještaj
 * Sada prima paid_total, paid_count, free_count (novi parametri)
 */
class YearlyFinanceReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $year;
    public $financeData;
    public $paid_total;
    public $paid_count;
    public $free_count;
    public $title;

    /**
     * Konstruktor
     *
     * @param int $year
     * @param \Illuminate\Support\Collection|array $financeData
     * @param float $paid_total
     * @param int $paid_count
     * @param int $free_count
     * @param string|null $title
     */
    public function __construct($year, $financeData, $paid_total, $paid_count, $free_count, $title = null)
    {
        $this->year = $year;
        $this->financeData = $financeData;
        $this->paid_total = $paid_total;
        $this->paid_count = $paid_count;
        $this->free_count = $free_count;
        $this->title = $title ?? 'Godišnji finansijski izvještaj - Kotor Bus';
    }

    /**
     * Priprema email sa godišnjim finansijskim izvještajem u pdf-u
     */
    public function build()
    {
        // Pravi PDF sa novim parametrima
        $pdf = Pdf::loadView('reports.yearly_finance_report_pdf', [
            'year' => $this->year,
            'financeData' => $this->financeData,
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
            ->view('emails.blank')
            ->attachData($pdf->output(), 'godisnji_finansijski_izvjestaj.pdf', ['mime' => 'application/pdf']);
    }
}