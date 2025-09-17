<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class YearlyVehicleReservationReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $year;
    public $reservationsByType;

    /**
     * Konstruktor - prosljeđuje podatke za izvještaj
     */
    public function __construct($year, $reservationsByType)
    {
        $this->year = $year;
        $this->reservationsByType = $reservationsByType;
    }

    /**
     * Priprema email sa godišnjim izvještajem o rezervacijama po tipu vozila u pdf-u
     */
    public function build()
    {
        // Prosleđujemo podatke kao array sa jasnim ključevima
        $pdf = Pdf::loadView('reports.yearly_vehicle_reservation_report_pdf', [
            'year' => $this->year,
            'reservationsByType' => $this->reservationsByType,
        ]);

        return $this->subject('Godišnji izvještaj o rezervacijama po tipu vozila')
            ->view('emails.blank') // koristi prazan view
            ->attachData(
                $pdf->output(),
                'godisnji_izvjestaj_rezervacije_po_voznom_parku.pdf',
                ['mime' => 'application/pdf']
            );
    }
}