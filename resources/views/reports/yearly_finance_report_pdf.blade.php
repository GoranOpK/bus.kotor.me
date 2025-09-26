<<<<<<< HEAD
<!--
Godišnji finansijski izvještaj - prikazuje podatke o plaćenim i besplatnim rezervacijama za cijelu godinu, kao i prihod po mjesecima.
Ispravljeno: svi znakovi čćšđž i € prikazuju se ispravno!
-->
=======
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
<<<<<<< HEAD
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>{{ $title ?? 'Godišnji finansijski izvještaj - Kotor Bus' }}</title>
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
    <!-- Naslov izvještaja -->
    <h2>{{ $title ?? 'Godišnji finansijski izvještaj - Kotor Bus' }}</h2>
    <!-- Prikaz godine izvještaja -->
    <p>Godina: {{ $year }}</p>

    <!-- Tabela za plaćene rezervacije -->
    <table>
        <thead>
            <tr>
                <th colspan="2" style="background: #e0e0e0;">Plaćene rezervacije</th>
            </tr>
            <tr>
                <th>Ukupan prihod (paid)</th>
                <th>Ukupan broj transakcija (paid)</th>
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

    <!-- Tabela za besplatne rezervacije -->
    <table>
        <thead>
            <tr>
                <th colspan="2" style="background: #e0e0e0;">Besplatne rezervacije</th>
            </tr>
            <tr>
                <th>Ukupan broj transakcija (free)</th>
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

    <br>

    <!-- Tabela sa prihodima po mjesecima -->
=======
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
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
    <table>
        <thead>
            <tr>
                <th>Mjesec</th>
<<<<<<< HEAD
                <th>Prihod (paid)</th>
=======
                <th>Prihod</th>
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
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
<<<<<<< HEAD
                    <td>{{ number_format($row['prihod'] ?? 0, 2, ',', '.') }}&nbsp;€</td>
=======
                    <td>{{ number_format($row['prihod'] ?? 0, 2, ',', '.') }} €</td>
>>>>>>> af255a2bafe1d3f8ed06ac5fb77cd16c44953019
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>