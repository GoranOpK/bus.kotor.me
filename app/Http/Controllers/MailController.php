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

        // Generisanje samo Invoice PDF-a
        $invoicePdf = Pdf::loadView('pdfs.invoice', [
            'user_name' => $validated['user_name'],
            'amount' => $validated['amount'],
            // ...
        ]);

        // Slanje e-maila sa samo jednim PDF-om (Invoice)
        Mail::to($validated['email'])->send(
            new PaymentReservationConfirmationMail(
                $validated['user_name'],
                $invoicePdf->output() // raw PDF data
            )
        );

        return response()->json(['message' => 'Payment confirmation sent with PDF attachment.']);
    }
}