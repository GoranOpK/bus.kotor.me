<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
<<<<<<< HEAD
    <title>Bezgotovinski račun</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 14px; color: #000; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .upper { text-transform: uppercase; }
        .header { margin-bottom: 8px; }
        .sub-header { margin-bottom: 2px; }
        .line { border-bottom: 1px dashed #000; margin: 6px 0; }
        .table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .table th, .table td { padding: 2px 4px; text-align: left; }
        .table th { border-bottom: 1px solid #000; }
        .footer { margin-top: 15px; font-size: 12px; }
        .qr { text-align: center; margin: 6px 0 3px 0; }
        .small { font-size: 11px; }
    </style>
</head>
<body>
    <table style="width:100%; border-collapse:collapse; margin-bottom:8px;">
        <tr>
            <td style="width:70px; vertical-align:top;">
                <img src="src/logo_kotor.png" alt="Opština Kotor" style="height:95px;">
            </td>
            <td style="text-align:center; vertical-align:top;">
                <div class="header center upper bold">
                    BEZGOTOVINSKI RAČUN<br>
                    OPŠTINA KOTOR<br>
                    Stari grad 317, KOTOR<p>
                    
                        <span class="small">
                        PIB: 02012936<br>
                        PDV broj: 92/31 02634 4
                        </span>
                    

                </div>
            </td>
        </tr>
    </table>
   
    <div class="line"></div>
    <div class="center bold" style="margin-bottom:5px;">
        NAKNADA ZA ISKORIŠĆAVANJE KULTURNIH DOBARA
    </div>
    <div class="small">
        Račun: <span class="bold">{{ $reservation->merchant_transaction_id }}</span><br>
        Vrijeme: <span class="bold">{{ \Carbon\Carbon::parse($reservation->fiscal_date)->format('d.m.Y H:i') }}</span><br>
        Izdato: <span class="bold">OPŠTINA KOTOR</span>
    </div>
    <div class="line"></div>
    <table class="table small">
        <tr>
            <th>Naziv</th>
            <th>Cijena</th>
            <th>Količina</th>
            <th>Porez</th>
            <th>Ukupno</th>
        </tr>
        <tr>
            <td>{{ $reservation->vehicleType->description_vehicle ?? 'Naknada' }}</td>
            <td>{{ number_format($reservation->vehicleType->price ?? 0, 2) }}</td>
            <td>1</td>
            <td>0,00</td>
            <td>{{ number_format($reservation->vehicleType->price ?? 0, 2) }}</td>
        </tr>
    </table>
    <div class="small">
        <div style="float:left;">Ukupan iznos:</div>
        <div style="float:right;">{{ number_format($reservation->vehicleType->price ?? 0, 2) }}EUR</div>
        <div style="clear:both;"></div>
        <div style="float:left;">Ukupno:</div>
        <div style="float:right;">{{ number_format($reservation->vehicleType->price ?? 0, 2) }}EUR</div>
        <div style="clear:both;"></div>
    </div>
    <div class="small" style="margin-top:4px;">
        Oslobođenje (oslobođeno od javnog interesa, čl.26 Zakon o PDV-u)
    </div>
    <div class="line"></div>
    <div class="small">
        IKOF: <span class="bold">{{ $reservation->fiscal_ikof }}</span><br>
        JIKR: <span class="bold">{{ $reservation->fiscal_jir }}</span>
    </div>
    <div class="qr">
        @if($qrBase64)
            <img src="{{ $qrBase64 }}" style="width:110px; height:110px;">
        @endif
    </div>
    <div class="small center" style="margin-top:2px;">
        Korisnik: {{ $reservation->user_name }} |
    {{ $reservation->license_plate }}
    @if(isset($reservation->passengers) && $reservation->passengers)
        | {{ $reservation->passengers }} PAX
    @endif
    </div>
    <div class="footer center small">
        www.primatech.me<br>
        <span style="font-size:10px;">Ovaj račun je generisan automatski i važi kao fiskalni dokument.</span>
=======
    <title>Invoice</title>
    <style>
        /* Stilovi za PDF prikaz */
        body { font-family: DejaVu Sans, sans-serif; }
        .header { text-align: center; }
        .details { margin: 20px 0; }
        .footer { margin-top: 40px; font-size: 0.9em; color: #777; }
    </style>
</head>
<body>
    <!-- Zaglavlje fakture -->
    <div class="header">
        <h2>Reservation Service Invoice</h2>
    </div>

    <!-- Detalji o rezervaciji i korisniku -->
    <div class="details">
        <p><strong>Customer Name:</strong> {{ $reservation->user_name }}</p>
        <p><strong>Email:</strong> {{ $reservation->email }}</p>
        <p><strong>Reservation Date:</strong> {{ $reservation->reservation_date->format('d.m.Y') }}</p>
        <p><strong>License Plate:</strong> {{ $reservation->license_plate }}</p>
        <p><strong>Vehicle Type:</strong> {{ $reservation->vehicleType->description_vehicle ?? '-' }}</p>
        <p><strong>Amount Paid:</strong> {{ number_format($reservation->vehicleType->price ?? 0, 2) }} €</p>
    </div>

    <!-- Futer sa napomenom o validnosti fakture -->
    <div class="footer">
        <hr>
        <p>This invoice has been generated automatically and is valid as a fiscal document.</p>
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
    </div>
</body>
</html>