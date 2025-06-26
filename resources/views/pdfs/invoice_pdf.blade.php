<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
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
    </div>
</body>
</html>