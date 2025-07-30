<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class InvoiceController extends Controller
{
    public function testFiskalniInvoice()
{
    $company = [
        'name'    => 'OPŠTINA KOTOR',
        'address' => 'Stari grad 317, KOTOR',
        'pib'     => '02012936',
        'pdv'     => '92/31/02634/4',
    ];
    $customer = [
        'name' => 'Test Korisnik',
        'pib'  => '',
    ];
    $items = [[
        'description' => 'Naknada autobusa 4',
        'price'       => 50.00,
        'qty'         => 1,
        'tax_label'   => 'Oslobođenje',
        'tax_rate'    => 0.00,
        'total'       => 50.00,
    ]];
    $invoice = (object)[
        'number'          => 'bs/02/06/21/2025/v416h0f16',
        'created_at'      => now(),
        'operator_name'   => 'Marko Krkovic',
		'payment_type'    => 'Online' ,
        'total'           => 50.00,
		'ikof'            => '786f170e0497255a28f4f2f48d7436',
        'jir'             => '276ab0bf-b9a9-431d-bf41-6b54e4e88',
        'note'            => 'ADRIATIC | PG JH1952 | 20 PAX',
        'free_reason'     => 'Oslobođeno od javnog interesa (čl.26)',
        'qr_code_base64'  => null,
    ];

    // QR kod generiši ispravno
    if (!empty($invoice->jir)) {
        $qrString = $invoice->jir;
        $qrCode = new QrCode($qrString); // << OVO JE ISPRAVNO!
        $writer = new PngWriter();
        $qrResult = $writer->write($qrCode);
        $invoice->qr_code_base64 = base64_encode($qrResult->getString());
    }

    $pdf = Pdf::loadView('pdfs.invoice_pdf', compact('company', 'customer', 'items', 'invoice'));
    return $pdf->stream('test_fiskalni_racun.pdf');
}
}