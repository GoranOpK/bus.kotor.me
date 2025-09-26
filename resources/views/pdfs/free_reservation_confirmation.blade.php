<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Potvrda besplatne rezervacije</title>
    <style>
        @page {
            size: A4;
            margin: 20mm 15mm 18mm 15mm;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 13px;
            border-bottom: 2px solid #333;
            padding-bottom: 8px;
        }
        .header h1 {
            margin: 0 0 8px 0;
            font-size: 18px;
        }
        .header h2 {
            margin: 0 0 4px 0;
            font-size: 14px;
        }
        .header p {
            margin: 0;
            font-size: 10px;
        }
        .free-notice {
            background-color: #f8f8f8;
            border: 2px solid #7a1018;
            padding: 10px 8px;
            margin: 14px 0;
            border-radius: 5px;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            color: #7a1018;
        }
        .section {
            margin-bottom: 12px;
        }
        .section-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 7px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 2px;
        }
        .row {
            display: flex;
            margin-bottom: 2px;
            page-break-inside: avoid;
        }
        .label {
            font-weight: bold;
            width: 140px;
            font-size: 11px;
        }
        .value {
            flex: 1;
            font-size: 11px;
        }
        .total {
            font-size: 15px;
            font-weight: bold;
            margin-top: 10px;
            padding: 6px 8px;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        img {
            height: 70px;
        }
        /* Prevent page breaks inside main blocks */
        .section, .header, .free-notice, .total, .footer {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
<div class="container">
    <table style="width:100%; border-collapse:collapse; margin-bottom:5px;">
        <tr>
            <td style="width:63px; vertical-align:top;">
                <img src="src/logo_kotor.png" alt="Opština Kotor">
            </td>
            <td style="text-align:center; vertical-align:top;">
                <div class="header">
                    <h1>POTVRDA BESPLATNE REZERVACIJE</h1>
                    <h2>OPŠTINA KOTOR</h2>
                    <p>Stari grad 317, KOTOR</p>
                </div>
            </td>
        </tr>
    </table>

    <div class="free-notice">
        BESPLATNA REZERVACIJA<br>
        Ova rezervacija je besplatna za odabrane termine
    </div>

    <div class="section">
        <div class="section-title">Podaci o rezervaciji</div>
        <div class="row">
            <div class="label">Broj rezervacije:</div>
            <div class="value">{{ $reservation->id }}</div>
        </div>
        <div class="row">
            <div class="label">Datum rezervacije:</div>
            <div class="value">{{ \Carbon\Carbon::parse($reservation->reservation_date)->format('d.m.Y') }}</div>
        </div>
        <div class="row">
            <div class="label">Status:</div>
            <div class="value"><strong style="color: #7a1018;">Besplatna rezervacija</strong></div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Podaci o korisniku</div>
        <div class="row">
            <div class="label">Naziv kompanije:</div>
            <div class="value">{{ $reservation->user_name }}</div>
        </div>
        <div class="row">
            <div class="label">Email:</div>
            <div class="value">{{ $reservation->email }}</div>
        </div>
        <div class="row">
            <div class="label">Država:</div>
            <div class="value">{{ $reservation->country }}</div>
        </div>
        <div class="row">
            <div class="label">Registarske tablice:</div>
            <div class="value">{{ $reservation->license_plate }}</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Detalji rezervacije</div>
        <div class="row">
            <div class="label">Tip vozila:</div>
            <div class="value">{{ $reservation->vehicleType->description_vehicle ?? 'N/A' }}</div>
        </div>
        <div class="row">
            <div class="label">Vrijeme dolaska:</div>
            <div class="value">{{ $reservation->dropOffTimeSlot->time_slot ?? 'N/A' }}</div>
        </div>
        <div class="row">
            <div class="label">Vrijeme odlaska:</div>
            <div class="value">{{ $reservation->pickUpTimeSlot->time_slot ?? 'N/A' }}</div>
        </div>
    </div>

    <div class="total">
        <div class="row">
            <div class="label">Ukupan iznos:</div>
            <div class="value">
                <strong style="color: #7a1018; font-size: 18px;">0,00 €</strong>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>Ova potvrda je automatski generisana od strane sistema Opštine Kotor.</p>
        <p>Za dodatne informacije kontaktirajte: bus@kotor.me</p>
        <p>
            Generisano: {{ now()->format('d.m.Y H:i:s') }}
        </p>
    </div>
</div>
</body>
</html>