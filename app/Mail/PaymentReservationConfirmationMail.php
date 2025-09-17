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
<<<<<<< HEAD
=======
    protected $confirmationPdf;
>>>>>>> edd871dd4444f817be418d934462960767b66424
    protected $isFreeReservation;

    /**
     * Create a new message instance.
     *
     * @param string $userName
     * @param mixed $invoicePdf
<<<<<<< HEAD
     * @param bool $isFreeReservation
     */
    public function __construct($userName, $invoicePdf, $isFreeReservation = false)
    {
        $this->userName = $userName;
        $this->invoicePdf = $invoicePdf;
=======
     * @param mixed $confirmationPdf
     * @param bool $isFreeReservation
     */
    public function __construct($userName, $invoicePdf, $confirmationPdf = null, $isFreeReservation = false)
    {
        $this->userName = $userName;
        $this->invoicePdf = $invoicePdf;
        $this->confirmationPdf = $confirmationPdf;
>>>>>>> edd871dd4444f817be418d934462960767b66424
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
        
<<<<<<< HEAD
        return $this->subject($subject)
=======
        $mail = $this->subject($subject)
>>>>>>> edd871dd4444f817be418d934462960767b66424
            ->view('emails.payment_confirmation')
            ->with([
                'user_name' => $this->userName
            ])
            ->attachData($this->invoicePdf, $attachmentName, ['mime' => 'application/pdf']);
<<<<<<< HEAD
=======
            
        // Priloži confirmation PDF ako postoji
        if ($this->confirmationPdf) {
            $mail->attachData($this->confirmationPdf, 'Confirmation.pdf', ['mime' => 'application/pdf']);
        }
        
        return $mail;
>>>>>>> edd871dd4444f817be418d934462960767b66424
    }
}