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

    /**
     * Create a new message instance.
     *
     * @param string $userName
     * @param mixed $invoicePdf
     * @param mixed $confirmationPdf
     */
<<<<<<< HEAD
    public function __construct($userName, $invoicePdf, $confirmationPdf = null)
=======
    public function __construct($userName, $invoicePdf, $confirmationPdf)
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
    {
        $this->userName = $userName;
        $this->invoicePdf = $invoicePdf;
        $this->confirmationPdf = $confirmationPdf;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $mail = $this->subject('Payment Notification')
            ->view('emails.payment_confirmation')
            ->with([
                'user_name' => $this->userName
            ])
<<<<<<< HEAD
            ->attachData($this->invoicePdf, 'Invoice.pdf', ['mime' => 'application/pdf']);
            
        // Priloži confirmation PDF ako postoji
        if ($this->confirmationPdf) {
            $mail->attachData($this->confirmationPdf, 'Confirmation.pdf', ['mime' => 'application/pdf']);
        }
        
        return $mail;
=======
            ->attachData($this->invoicePdf, 'Invoice.pdf', ['mime' => 'application/pdf'])
            ->attachData($this->confirmationPdf, 'PaymentConfirmation.pdf', ['mime' => 'application/pdf']);
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
    }
}