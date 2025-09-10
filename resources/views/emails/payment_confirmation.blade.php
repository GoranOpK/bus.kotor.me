<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Notification</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 14px; color: #222; }
        .footer { margin-top: 40px; font-size: 12px; color: #888; }
        .language-section { margin-bottom: 30px; }
        .language-title { font-weight: bold; color: #333; margin-bottom: 10px; }
    </style>
</head>
<body>
    @php
        // Postavi default jezik pošto smo odustali od višejezičnosti
        $user_language = $user_language ?? 'en';
    @endphp

    @if($user_language === 'en')
        <!-- English Section -->
        <div class="language-section">
            <p>Subject: Confirmation of Parking Reservation Payment - Municipality of Kotor</p>
            <p>Dear,</p>
            <p>Your reservation has been successfully confirmed!</p>
            <p>Attached to this email you will find your Invoice for the payment.</p>
            <p>Please keep it for your records.</p>
            <p>Best regards,<br>
            Municipality of Kotor</p>
        </div>
    @else
        <!-- Montenegrin Section -->
        <div class="language-section">
            <p>Predmet: Potvrda plaćanja rezervacije parkinga - Opština Kotor</p>
            <p>Poštovani,</p>
            <p>Vaša rezervacija je uspješno potvrđena!</p>
            <p>U prilogu ovog email-a pronaći ćete vašu fakturu za plaćanje.</p>
            <p>Molimo vas da je sačuvate za svoju evidenciju.</p>
            <p>S poštovanjem,<br>
            Opština Kotor</p>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        @if($user_language === 'en')
            <div>This message was generated automatically {{ \Carbon\Carbon::now()->format('d.m.Y H:i') }}</div>
        @else
            <div>Ova poruka je automatski generisana {{ \Carbon\Carbon::now()->format('d.m.Y H:i') }}</div>
        @endif
    </div>
</body>
</html>