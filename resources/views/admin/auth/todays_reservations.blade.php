<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>– Rezervisani Slotovi</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9fa; margin: 0; padding: 40px; }
        h2 { text-align: center; margin-bottom: 24px; }
        .interval-block { background: #fff; margin: 16px auto; padding: 18px 24px; width: 80%; border-radius: 10px; box-shadow: 0 2px 8px #e3e6f3; }
        .interval-title { font-size: 1.2em; color: #2563eb; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px 6px; text-align: center; }
        th { background: #f2f6fc; color: #222; }
        .empty { color: #bbb; font-style: italic; }
        .server-time { text-align: right; font-size: 0.95em; color: #888; margin-bottom: 6px; }
        .refresh-hint { text-align: right; font-size: 0.85em; color: #aaa; }
    </style>
</head>
<body>
    <h2>– Rezervisani Slotovi (danas)</h2>
    <div class="server-time">
        Server vreme: <span id="serverTime">{{ $server_time ?? '' }}</span>
        <span class="refresh-hint">(stranica se automatski osvežava na 5 min)</span>
    </div>
    <div id="intervals">
        @forelse($intervals as $interval)
            <div class="interval-block">
                <div class="interval-title">{{ $interval['interval'] }}</div>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tip vozila</th>
                            <th>Registarska oznaka</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($interval['reservations'] as $ix => $res)
                            <tr>
                                <td>{{ $ix + 1 }}</td>
                                <td>{{ $res['vehicle_type'] }}</td>
                                <td>{{ $res['license_plate'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="empty">Nema rezervacija</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @empty
            <div class="interval-block">
                <div class="interval-title">Nema rezervisanih vremenskih slotova za danas</div>
            </div>
        @endforelse
    </div>
    <script>
        // Automatsko osvežavanje na 5 minuta
        setTimeout(function() { window.location.reload(); }, 5 * 60 * 1000);
    </script>
</body>
</html>