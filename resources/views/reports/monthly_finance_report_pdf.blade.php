<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Mjesečni finansijski izvještaj - Kotor Bus' }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; margin-top: 24px; }
        th, td { border: 1px solid #cccccc; padding: 8px 12px; text-align: left; }
        th { background: #eeeeee; }
    </style>
</head>
<body>
    <h2>{{ $title ?? 'Mjesečni finansijski izvještaj - Kotor Bus' }}</h2>
    <p>Mjesec: {{ $month }} / {{ $year }}</p>
    <table>
        <thead>
            <tr>
                <th>Ukupan prihod</th>
                <th>Broj transakcija</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ number_format($financeData->ukupno ?? 0, 2, ',', '.') }} €</td>
                <td>{{ $financeData->broj_transakcija ?? 0 }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>