<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
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
    </div>
</body>
</html>