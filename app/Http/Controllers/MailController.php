<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\PaymentReservationConfirmationMail;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class MailController extends Controller
{
    public function sendPaymentConfirmation(Request $request)
    {
        // Validacija requesta
        $validated = $request->validate([
            'email' => 'required|email',
            'user_name' => 'required|string',
            'amount' => 'required|numeric',
            'transaction_id' => 'required|string',
            // dodaj još polja po potrebi
        ]);

        // Generiši samo invoice PDF
        $pdf1 = Pdf::loadView('pdfs.invoice', [
            'user_name' => $validated['user_name'],
            'amount' => $validated['amount'],
            // ...
        ]);

        // Slanje e-maila samo sa jednim PDF-om kao prilog (račun)
        Mail::to($validated['email'])->send(
            new PaymentReservationConfirmationMail(
                $validated['user_name'],
<<<<<<< HEAD
                $pdf1->output(), // raw PDF data (račun)
                null,            // confirmationPdf je null - neće biti dodat kao atačment
                false            // nije besplatna rezervacija
=======
                $pdf1->output(), // raw PDF data
                $pdf2->output(),  // raw PDF data
                false // nije besplatna rezervacija
>>>>>>> edd871dd4444f817be418d934462960767b66424
            )
        );

        return response()->json(['message' => 'Payment confirmation sent with PDF attachment.']);
    }
}