<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Notification</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 14px; color: #222; }
        .footer { margin-top: 40px; font-size: 12px; color: #888; }
    </style>
</head>
<body>
    <!-- Pozdrav korisniku -->
    <p>Dear {{ $user_name }},</p>

    <!-- Poruka o potvrdi rezervacije -->
    <p>Your reservation has been <strong>successfully confirmed</strong>!</p>
    
    <!-- (Ovdje možeš dodati dodatne podatke o rezervaciji po potrebi) -->
    {{-- 
    <ul>
        <li>Reservation number: {{ $reservation_number ?? '' }}</li>
        <li>Date: {{ $reservation_date ?? '' }}</li>
    </ul>
    --}}

    <!-- Poruka korisniku da je u prilogu faktura -->
    <p>Attached to this email you will find your <strong>Invoice</strong> for the payment.</p>
    <p>Please keep it for your records.</p>
    
    <!-- Pozdrav od opštine -->
    <p>Best regards,<br>
    Municipality of Kotor</p>
    <!-- Futer sa napomenom o automatskoj poruci -->
    <div class="footer">
        This message was generated automatically {{ \Carbon\Carbon::now()->format('d.m.Y H:i') }}
    </div>
</body>
</html>