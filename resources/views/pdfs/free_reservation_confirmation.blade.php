<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Potvrda besplatne rezervacije</title>
    <style>
<<<<<<< HEAD
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
=======
        body {
<<<<<<< HEAD
            font-family: 'DejaVu Sans', Arial, sans-serif;
=======
            font-family: Arial, sans-serif;
>>>>>>> edd871dd4444f817be418d934462960767b66424
            margin: 0;
            padding: 20px;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0 0 10px 0;
            font-size: 18px;
        }
        .header h2 {
            margin: 0 0 5px 0;
            font-size: 16px;
        }
        .header p {
            margin: 0;
            font-size: 12px;
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
        }
        .free-notice {
            background-color: #f8f8f8;
            border: 2px solid #7a1018;
<<<<<<< HEAD
            padding: 10px 8px;
            margin: 14px 0;
            border-radius: 5px;
            text-align: center;
            font-size: 14px;
=======
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            text-align: center;
            font-size: 16px;
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
            font-weight: bold;
            color: #7a1018;
        }
        .section {
<<<<<<< HEAD
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
=======
            margin-bottom: 20px;
        }
        .section-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .row {
            display: flex;
            margin-bottom: 5px;
        }
        .label {
            font-weight: bold;
            width: 150px;
        }
        .value {
            flex: 1;
        }
        .total {
            font-size: 16px;
            font-weight: bold;
            margin-top: 20px;
            padding: 10px;
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
            background-color: #f0f0f0;
            border: 1px solid #ccc;
        }
        .footer {
<<<<<<< HEAD
            margin-top: 20px;
=======
            margin-top: 40px;
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ccc;
<<<<<<< HEAD
            padding-top: 10px;
        }
        img {
            height: 70px;
        }
        /* Prevent page breaks inside main blocks */
        .section, .header, .free-notice, .total, .footer {
            page-break-inside: avoid;
=======
            padding-top: 20px;
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
        }
    </style>
</head>
<body>
<<<<<<< HEAD
<div class="container">
    <table style="width:100%; border-collapse:collapse; margin-bottom:5px;">
        <tr>
            <td style="width:63px; vertical-align:top;">
                <img src="src/logo_kotor.png" alt="Opština Kotor">
=======
    <table style="width:100%; border-collapse:collapse; margin-bottom:8px;">
        <tr>
            <td style="width:70px; vertical-align:top;">
                <img src="src/logo_kotor.png" alt="Opština Kotor" style="height:95px;">
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
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
<<<<<<< HEAD
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
=======
        <div class="section-title">
            Podaci o rezervaciji
        </div>
        <div class="row">
            <div class="label">
                Broj rezervacije:
            </div>
            <div class="value">{{ $reservation->id }}</div>
        </div>
        <div class="row">
            <div class="label">
                Datum rezervacije:
            </div>
            <div class="value">{{ \Carbon\Carbon::parse($reservation->reservation_date)->format('d.m.Y') }}</div>
        </div>
        <div class="row">
            <div class="label">
                Status:
            </div>
            <div class="value">
                <strong style="color: #7a1018;">
                    Besplatna rezervacija
                </strong>
            </div>
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
        </div>
    </div>

    <div class="section">
<<<<<<< HEAD
        <div class="section-title">Podaci o korisniku</div>
        <div class="row">
            <div class="label">Naziv kompanije:</div>
=======
        <div class="section-title">
            Podaci o korisniku
        </div>
        <div class="row">
            <div class="label">
                Naziv kompanije:
            </div>
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
            <div class="value">{{ $reservation->user_name }}</div>
        </div>
        <div class="row">
            <div class="label">Email:</div>
            <div class="value">{{ $reservation->email }}</div>
        </div>
        <div class="row">
<<<<<<< HEAD
            <div class="label">Država:</div>
            <div class="value">{{ $reservation->country }}</div>
        </div>
        <div class="row">
            <div class="label">Registarske tablice:</div>
=======
            <div class="label">
                Država:
            </div>
            <div class="value">{{ $reservation->country }}</div>
        </div>
        <div class="row">
            <div class="label">
                Registarske tablice:
            </div>
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
            <div class="value">{{ $reservation->license_plate }}</div>
        </div>
    </div>

    <div class="section">
<<<<<<< HEAD
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
=======
        <div class="section-title">
            Detalji rezervacije
        </div>
        <div class="row">
            <div class="label">
                Tip vozila:
            </div>
            <div class="value">{{ $reservation->vehicleType->description_vehicle ?? 'N/A' }}</div>
        </div>
        <div class="row">
            <div class="label">
                Vrijeme dolaska:
            </div>
            <div class="value">{{ $reservation->dropOffTimeSlot->time_slot ?? 'N/A' }}</div>
        </div>
        <div class="row">
            <div class="label">
                Vrijeme odlaska:
            </div>
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
            <div class="value">{{ $reservation->pickUpTimeSlot->time_slot ?? 'N/A' }}</div>
        </div>
    </div>

    <div class="total">
        <div class="row">
<<<<<<< HEAD
            <div class="label">Ukupan iznos:</div>
=======
            <div class="label">
                Ukupan iznos:
            </div>
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
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
<<<<<<< HEAD
</div>
</body>
</html>
=======
</body>
</html>
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
