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

        // Generisanje PDF-ova u letu:
        $pdf1 = Pdf::loadView('pdfs.invoice', [
            'user_name' => $validated['user_name'],
            'amount' => $validated['amount'],
            // ...
        ]);
        $pdf2 = Pdf::loadView('pdfs.confirmation', [
            'transaction_id' => $validated['transaction_id'],
            // ...
        ]);

        // Slanje e-maila sa oba PDF-a kao prilog
        Mail::to($validated['email'])->send(
            new PaymentReservationConfirmationMail(
                $validated['user_name'],
                $pdf1->output(), // raw PDF data
                $pdf2->output(),  // raw PDF data
                false // nije besplatna rezervacija
            )
        );

        return response()->json(['message' => 'Payment confirmation sent with PDF attachments.']);
    }
}