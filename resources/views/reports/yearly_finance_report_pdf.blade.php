<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Godišnji finansijski izvještaj - Kotor Bus</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #444; padding: 5px; text-align: left; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h1>Godišnji finansijski izvještaj - Kotor Bus</h1>
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
                    <td>{{ number_format($row['prihod'] ?? 0, 2, ',', '.') }} &euro;</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>