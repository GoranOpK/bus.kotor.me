<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Potvrda besplatne rezervacije</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 14px; color: #222; }
        .footer { margin-top: 40px; font-size: 12px; color: #888; }
        .language-section { margin-bottom: 30px; }
        .language-title { font-weight: bold; color: #333; margin-bottom: 10px; }
    </style>
</head>
<body>
    @php
        $user_language = $user_language ?? 'en';
    @endphp
    
    @if($user_language === 'en')
        <!-- English Section -->
        <div class="language-section">
            <p>Dear {{ $user_name }},</p>
            <p>Your free parking reservation has been successfully created.</p>
            <p>Attached to this email you will find the free parking reservation confirmation.</p>
            <p>Please keep this confirmation for your records.</p>
            <p>Best regards,<br>
            Municipality of Kotor</p>
        </div>
    @else
        <!-- Montenegrin Section -->
        <div class="language-section">
            <p>Poštovani {{ $user_name }},</p>
            <p>Vaša besplatna rezervacija parkinga je uspješno kreirana.</p>
            <p>U prilogu se nalazi potvrda o besplatnoj rezervaciji parkinga.</p>
            <p>Molimo vas da sačuvate ovu potvrdu za svoje evidencije.</p>
            <p>Srdačan pozdrav,<br>
            Opština Kotor</p>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        @if($user_language === 'en')
            <div>This message was generated automatically {{ \Carbon\Carbon::now()->format('d.m.Y H:i') }}</div>
        @else
            <div>Ova poruka je automatski generirana {{ \Carbon\Carbon::now()->format('d.m.Y H:i') }}</div>
        @endif
    </div>
</body>
</html> 