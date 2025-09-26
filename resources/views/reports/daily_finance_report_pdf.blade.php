<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>{{ $title ?? 'Dnevni finansijski izvještaj - Kotor Bus' }}</title>
    <style>
        body { 
            font-family: 'DejaVu Sans', Arial, sans-serif; 
            font-size: 12px;
        }
        table { border-collapse: collapse; width: 100%; margin-top: 24px; }
        th, td { border: 1px solid #cccccc; padding: 8px 12px; text-align: left; }
        th { background: #eeeeee; font-weight: bold; }
        h2 { font-family: 'DejaVu Sans', Arial, sans-serif; font-weight: bold; }
    </style>
</head>
<body>
    <h2>{{ $title ?? 'Dnevni finansijski izvještaj - Kotor Bus' }}</h2>
    <p>Datum: {{ \Carbon\Carbon::parse($date)->format('d.m.Y') }}</p>

    <table>
        <thead>
            <tr>
                <th colspan="2" style="background: #e0e0e0;">Plaćene rezervacije</th>
            </tr>
            <tr>
                <th>Ukupan prihod (paid)</th>
                <th>Broj transakcija (paid)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ number_format($paid_total ?? 0, 2, ',', '.') }}&nbsp;€</td>
                <td>{{ $paid_count ?? 0 }}</td>
            </tr>
        </tbody>
    </table>

    <br>

    <table>
        <thead>
            <tr>
                <th colspan="2" style="background: #e0e0e0;">Besplatne rezervacije</th>
            </tr>
            <tr>
                <th>Broj transakcija (free)</th>
                <th>Napomena</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $free_count ?? 0 }}</td>
                <td>Sve besplatne rezervacije imaju iznos 0&nbsp;€</td>
            </tr>
        </tbody>
    </table>
</body>
</html>