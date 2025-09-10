<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class DailyVehicleReservationReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $reservationsByType;
    public $date;

    public function __construct($reservationsByType, $date)
    {
        $this->reservationsByType = $reservationsByType;
        $this->date = $date;
    }

    public function build()
    {
        $pdf = Pdf::loadView('reports.daily_vehicle_reservation_report_pdf', [
            'date' => $this->date,
            'reservationsByType' => $this->reservationsByType,
        ]);
        return $this->subject('Dnevni izvještaj o rezervacijama po tipu vozila')
            ->view('emails.blank') // koristi prazan view
            ->attachData(
                $pdf->output(),
                'dnevni_izvjestaj_rezervacije_po_voznom_parku.pdf',
                ['mime' => 'application/pdf']
            );
    }
}