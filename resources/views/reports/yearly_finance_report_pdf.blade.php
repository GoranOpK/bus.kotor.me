<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Godišnji finansijski izvještaj - Kotor Bus' }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; margin-top: 24px; }
        th, td { border: 1px solid #cccccc; padding: 8px 12px; text-align: left; }
        th { background: #eeeeee; }
    </style>
</head>
<body>
    <h2>{{ $title ?? 'Godišnji finansijski izvještaj - Kotor Bus' }}</h2>
    <p>Godina: {{ $year }}</p>
    <p><strong>Ukupan prihod:</strong> {{ number_format($totalFinance ?? 0, 2, ',', '.') }} €</p>
    <p><strong>Ukupan broj transakcija:</strong> {{ $totalCount ?? 0 }}</p>
    <table>
        <thead>
            <tr>
                <th>Mjesec</th>
                <th>Prihod</th>
            </tr>
        </thead>
        <tbody>
            @php
                $mjeseci = [
                    1 => 'Januar', 2 => 'Februar', 3 => 'Mart', 4 => 'April',
                    5 => 'Maj', 6 => 'Jun', 7 => 'Jul', 8 => 'Avgust',
                    9 => 'Septembar', 10 => 'Oktobar', 11 => 'Novembar', 12 => 'Decembar'
                ];
            @endphp
            @foreach($financeData as $row)
                <tr>
                    <td>{{ $mjeseci[intval($row['mjesec'])] ?? $row['mjesec'] }}</td>
                    <td>{{ number_format($row['prihod'] ?? 0, 2, ',', '.') }} €</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>