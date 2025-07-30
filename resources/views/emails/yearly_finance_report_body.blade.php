<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Godišnji finansijski izvještaj - Kotor Bus</title>
</head>
<body>
    <h2>Godišnji finansijski izvještaj - Kotor Bus</h2>
    <p>Godina: {{ $year }}</p>
    <p>Ukupan prihod: <strong>{{ number_format($total, 2, ',', '.') }}€</strong></p>
    <p>U prilogu ovog emaila nalazi se PDF izvještaj.</p>
</body>
</html>