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
<<<<<<< HEAD
            ->view('emails.blank') // koristi prazan view
=======
            ->view('emails.empty')
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
            ->attachData(
                $pdf->output(),
                'dnevni_izvjestaj_rezervacije_po_voznom_parku.pdf',
                ['mime' => 'application/pdf']
            );
    }
}