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
    public function __construct($userName, $invoicePdf, $confirmationPdf)
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
        return $this->subject('Payment Notification')
            ->view('emails.payment_confirmation')
            ->with([
                'user_name' => $this->userName
            ])
            ->attachData($this->invoicePdf, 'Invoice.pdf', ['mime' => 'application/pdf'])
            ->attachData($this->confirmationPdf, 'PaymentConfirmation.pdf', ['mime' => 'application/pdf']);
    }
}