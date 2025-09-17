<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentReservationConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $userName;
    protected $invoicePdf;
    protected $isFreeReservation;

    /**
     * Create a new message instance.
     *
     * @param string $userName
     * @param mixed $invoicePdf
     * @param bool $isFreeReservation
     */
    public function __construct($userName, $invoicePdf, $isFreeReservation = false)
    {
        $this->userName = $userName;
        $this->invoicePdf = $invoicePdf;
        $this->isFreeReservation = $isFreeReservation;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Odredi subject na osnovu tipa rezervacije
        if ($this->isFreeReservation) {
            $subject = 'Potvrda besplatne rezervacije parkinga - Opština Kotor';
            $attachmentName = 'potvrda_besplatne_rezervacije.pdf';
        } else {
            $subject = 'Potvrda plaćanja rezervacije parkinga - Opština Kotor';
            $attachmentName = 'Invoice.pdf';
        }
        
        return $this->subject($subject)
            ->view('emails.payment_confirmation')
            ->with([
                'user_name' => $this->userName
            ])
            ->attachData($this->invoicePdf, $attachmentName, ['mime' => 'application/pdf']);
    }
}