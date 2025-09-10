<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Dnevni izvještaj o rezervacijama po tipu vozila - Kotor Bus' }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; margin-top: 24px; }
        th, td { border: 1px solid #cccccc; padding: 8px 12px; text-align: left; }
        th { background: #eeeeee; }
    </style>
</head>
<body>
    <h2>{{ $title ?? 'Dnevni izvještaj o rezervacijama po tipu vozila - Kotor Bus' }}</h2>
    <p>Datum: {{ \Carbon\Carbon::parse($date)->format('d.m.Y') }}</p>
    <table>
        <thead>
            <tr>
                <th>Tip vozila</th>
                <th>Broj rezervacija</th>
            </tr>
        </thead>
        <tbody>
        @php
            // Definiši sve tipove vozila sa crnogorskim nazivima
            $tipovi = [
                1 => 'PUTNIČKO VOZILO (4+1, 5+1, 6+1, 7+1 sjedišta)',
                2 => 'PUTNIČKO VOZILO (8+1 sjedišta)',
                3 => 'SREDNJI AUTOBUS (9-23 sjedišta)',
                4 => 'VELIKI AUTOBUS (PREKO 23 sjedišta)'
            ];
            // Pripremi lookup za rezervacije po tipu
            $rezervacijeLookup = [];
            foreach ($reservationsByType as $row) {
                $id = $row->vehicle_type_id ?? null;
                $broj = $row->broj_rezervacija ?? $row->count ?? 0;
                if ($id) {
                    $rezervacijeLookup[$id] = $broj;
                }
            }
        @endphp
        @foreach($tipovi as $id => $naziv)
            <tr>
                <td>{{ $naziv }}</td>
                <td>{{ $rezervacijeLookup[$id] ?? 0 }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>