<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Confirmation</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 14px; }
        .header { font-size: 18px; font-weight: bold; margin-bottom: 20px; }
        .section { margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="header">Payment Confirmation</div>
    <div class="section">Dear {{ $user_name }},</div>
    <div class="section">
        We confirm that we have received your payment.<br>
        Transaction ID: <strong>{{ $transaction_id }}</strong>
    </div>
    <div class="section">Amount: <strong>{{ $amount }} €</strong></div>
    <div class="section">Date: {{ \Carbon\Carbon::now()->format('d.m.Y H:i') }}</div>
    <div>Best regards,<br>Municipality of Kotor</div>
</body>
</html>