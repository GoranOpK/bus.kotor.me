<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Račun</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 14px; color: #000; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .upper { text-transform: uppercase; }
        .header { margin-bottom: 8px; }
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
                    Stari grad 317, KOTOR
                    <p>
                        <span class="small">
                        PIB: 02012936<br>
                        PDV broj: 92/31 02634 4
                        </span>
                    </p>
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
        Vrijeme: <span class="bold">
            @php
                // Koristi created_at jer je DATETIME tip i sadrži stvarno vreme kreiranja
                $fiscalDateTime = $reservation->created_at 
                    ? \Carbon\Carbon::parse($reservation->created_at)
                    : now();
            @endphp
            {{ $fiscalDateTime->format('d.m.Y H:i') }}
        </span><br>
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
        JIKR: <span class="bold">{{ $reservation->fiscal_jir }}</span><br>
        @php
            $internalNumber = '';
            if ($reservation->fiscal_qr) {
                // Izdvajanje ord broja iz fiscal_qr URL-a
                if (preg_match('/ord=(\d+)/', $reservation->fiscal_qr, $ordMatches)) {
                    $ordNumber = $ordMatches[1];
                    
                    // Izdvajanje godine iz crtd parametra
                    if (preg_match('/crtd=(\d{4})-\d{2}-\d{2}/', $reservation->fiscal_qr, $yearMatches)) {
                        $year = $yearMatches[1];
                        $internalNumber = $ordNumber . '/' . $year;
                    }
                }
            }
        @endphp
        @if($internalNumber)
            Interni broj: <span class="bold">{{ $internalNumber }}</span>
        @endif
    </div>
    <div class="qr">
        @if($qrBase64)
            <img src="{{ $qrBase64 }}" style="width:110px; height:110px;">
        @endif
    </div>
    <div class="footer center small" style="margin-top:8px;">
        www.primatech.me<br>
        <span style="font-size:10px;">
            Ovaj račun je generisan automatski i važi kao fiskalni dokument.
        </span>
    </div>
    <div class="line" style="margin-top:8px;"></div>
    
    <div style="margin-top:8px;">
        <div class="bold" style="font-size:13px; margin-bottom:4px; border-bottom:1px dashed #000; padding-bottom:2px;">
            Podaci o korisniku
        </div>
        <div class="small" style="margin-bottom:2px;">
            <span class="bold">Naziv kompanije:</span> {{ $reservation->user_name ?? 'N/A' }}
        </div>
        <div class="small" style="margin-bottom:2px;">
            <span class="bold">Email:</span> {{ $reservation->email ?? 'N/A' }}
        </div>
        <div class="small" style="margin-bottom:2px;">
            <span class="bold">Država:</span> {{ $reservation->country ?? 'N/A' }}
        </div>
        <div class="small" style="margin-bottom:4px;">
            <span class="bold">Registarske tablice:</span> {{ $reservation->license_plate ?? 'N/A' }}
        </div>
    </div>
    <div class="line"></div>
    
    <div style="margin-top:8px;">
        <div class="bold" style="font-size:13px; margin-bottom:4px; border-bottom:1px dashed #000; padding-bottom:2px;">
            Detalji rezervacije
        </div>
        <div class="small" style="margin-bottom:2px;">
            <span class="bold">Tip vozila:</span> {{ $reservation->vehicleType->description_vehicle ?? 'N/A' }}
        </div>
        <div class="small" style="margin-bottom:2px;">
            <span class="bold">Datum rezervacije:</span> {{ \Carbon\Carbon::parse($reservation->reservation_date)->format('d.m.Y') }}
        </div>
        <div class="small" style="margin-bottom:2px;">
            <span class="bold">Vrijeme dolaska:</span> {{ $reservation->dropOffTimeSlot->time_slot ?? 'N/A' }}
        </div>
        <div class="small" style="margin-bottom:4px;">
            <span class="bold">Vrijeme odlaska:</span> {{ $reservation->pickUpTimeSlot->time_slot ?? 'N/A' }}
        </div>
    </div>
</body>
</html>
