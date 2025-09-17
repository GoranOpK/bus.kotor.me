<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mjesečni izvještaj o rezervacijama po tipu vozila - Kotor Bus</title>
</head>
<body>
    <h2>Mjesečni izvještaj o rezervacijama po tipu vozila - Kotor Bus</h2>
    <p>Period: {{ $month }}/{{ $year }}</p>
    <p>Ukupno rezervacija: <strong>{{ $total_reservations }}</strong></p>
    <p>U prilogu ovog emaila nalazi se PDF izvještaj.</p>
</body>
</html>