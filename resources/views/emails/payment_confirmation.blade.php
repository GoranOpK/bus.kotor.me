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
        // Definiši kodove jezika za našu regiju
        $local_languages = ['me', 'sr', 'hr', 'ba', 'mk'];
        $user_language = $user_language ?? 'en';
        $user_name = $user_name ?? '';
    @endphp

    @if(in_array($user_language, $local_languages))
        <!-- Montenegrin Section -->
        <div class="language-section">
            <p>Predmet: Potvrda plaćanja rezervacije time slotova - Opština Kotor</p>
            <p>Poštovani{{ !empty($user_name) ? ', ' . $user_name : ',' }}</p>
            <p>Vaša rezervacija je uspješno potvrđena!</p>
            <p>U prilogu ovog email-a pronaći ćete vašu fakturu za plaćanje.</p>
            <p>Molimo vas da je sačuvate za svoju evidenciju.</p>
            <p>S poštovanjem,<br>
            Opština Kotor</p>
        </div>
    @else
        <!-- English Section -->
        <div class="language-section">
            <p>Subject: Confirmation of time slots reservation payment - Municipality of Kotor</p>
            <p>Dear{{ !empty($user_name) ? ', ' . $user_name : ',' }}</p>
            <p>Your reservation has been successfully confirmed!</p>
            <p>Attached to this email you will find your Invoice for the payment.</p>
            <p>Please keep it for your records.</p>
            <p>Best regards,<br>
            Municipality of Kotor</p>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        @if(in_array($user_language, $local_languages))
            <div>Ova poruka je automatski generisana {{ \Carbon\Carbon::now()->format('d.m.Y H:i') }}</div>
        @else
            <div>This message was generated automatically {{ \Carbon\Carbon::now()->format('d.m.Y H:i') }}</div>
        @endif
    </div>
</body>
</html>