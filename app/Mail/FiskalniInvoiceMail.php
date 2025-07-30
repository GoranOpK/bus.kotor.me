<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FiskalniInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    protected $pdfPath;

    /**
     * Create a new message instance.
     *
     * @param array $data
     * @param string $pdfPath
     */
    public function __construct($data, $pdfPath)
    {
        $this->data = $data;
        $this->pdfPath = $pdfPath;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Reservation confirmation and fiscal invoice')
            ->view('emails.fiskalni-invoice')
            ->with($this->data)
            ->attach($this->pdfPath, [
                'as' => 'FiscalInvoice.pdf',
                'mime' => 'application/pdf',
            ]);
    }
}