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
    protected $confirmationPdf;
    protected $isFreeReservation;
    protected $userLanguage;

    /**
     * Create a new message instance.
     *
     * @param string $userName
     * @param mixed $invoicePdf
     * @param mixed $confirmationPdf
     * @param bool $isFreeReservation
     * @param string $userLanguage
     */
    public function __construct($userName, $invoicePdf, $confirmationPdf = null, $isFreeReservation = false, $userLanguage = 'en')
    {
        $this->userName = $userName;
        $this->invoicePdf = $invoicePdf;
        $this->confirmationPdf = $confirmationPdf;
        $this->isFreeReservation = $isFreeReservation;
        $this->userLanguage = $userLanguage;
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
        
        $mail = $this->subject($subject)
            ->view('emails.payment_confirmation')
            ->with([
                'user_name' => $this->userName,
                'user_language' => $this->userLanguage
            ])
            ->attachData($this->invoicePdf, $attachmentName, ['mime' => 'application/pdf']);
            
        // Priloži confirmation PDF ako postoji
        if ($this->confirmationPdf) {
            $mail->attachData($this->confirmationPdf, 'Confirmation.pdf', ['mime' => 'application/pdf']);
        }
        
        return $mail;
    }
}