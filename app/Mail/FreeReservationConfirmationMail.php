<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FreeReservationConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $userName;
    protected $confirmationPdf;
    protected $userLanguage;

    /**
     * Create a new message instance.
     *
     * @param string $userName
     * @param mixed $confirmationPdf
     * @param string $userLanguage
     */
    public function __construct($userName, $confirmationPdf, $userLanguage = 'en')
    {
        $this->userName = $userName;
        $this->confirmationPdf = $confirmationPdf;
        $this->userLanguage = $userLanguage;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Potvrda besplatne rezervacije parkinga - Opština Kotor')
            ->view('emails.free_reservation_confirmation')
            ->with([
                'user_name' => $this->userName,
                'user_language' => $this->userLanguage
            ])
            ->attachData($this->confirmationPdf, 'potvrda_besplatne_rezervacije.pdf', ['mime' => 'application/pdf']);
    }
} 