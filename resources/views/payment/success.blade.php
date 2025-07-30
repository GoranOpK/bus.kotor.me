<!DOCTYPE html>
<html>
<head>
    <title>Payment Successful</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            text-align: center;
            background-color: #f5f5f5;
        }
        .success-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success-icon {
            color: #28a745;
            font-size: 48px;
            margin-bottom: 20px;
        }
        h1 {
            color: #28a745;
            margin-bottom: 20px;
        }
        .download-btn {
            background: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 20px 10px;
            text-decoration: none;
            display: inline-block;
        }
        .download-btn:hover {
            background: #0056b3;
        }
        .home-btn {
            background: #6c757d;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 20px 10px;
            text-decoration: none;
            display: inline-block;
        }
        .home-btn:hover {
            background: #545b62;
        }
        .info {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>Payment Successful!</h1>
    <p>Your payment has been received. The reservation is recorded.</p>

    <a href="/download-invoice/{{ $reservationId ?? '' }}" class="download-btn" style="display:inline-block;margin-top:20px;">
        📄 Preuzmi račun (PDF)
    </a>

    <script>
        // Sprečavanje duplog download-a - globalna provera
        if (window.downloadInProgress) {
            console.log('Download već u toku, preskačem...');
        } else {
            window.downloadInProgress = true;
            console.log('Postavljam globalni downloadInProgress = true');
            
            // Automatski download PDF-a nakon 2 sekunde
            setTimeout(function() {
            var reservationId = "{{ $reservationId ?? '' }}";
            console.log('Pokušavam download za rezervaciju:', reservationId);
            console.log('Timestamp:', new Date().toISOString());
            
            if (reservationId) {
                // Koristi fetch za download umesto window.open
                console.log('Započinjem fetch za /download-invoice/' + reservationId);
                fetch('/download-invoice/' + reservationId)
                    .then(response => {
                        console.log('Response status:', response.status);
                        console.log('Response headers:', response.headers);
                        console.log('Response ok:', response.ok);
                        
                        if (response.ok) {
                            console.log('Response je OK, konvertujem u blob');
                            return response.blob();
                        }
                        console.log('Response nije OK, bacaću grešku');
                        throw new Error('Network response was not ok. Status: ' + response.status);
                    })
                    .then(blob => {
                        console.log('Blob size:', blob.size);
                        console.log('Blob type:', blob.type);
                        
                        // Kreiraj link za download
                        const url = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.style.display = 'none';
                        a.href = url;
                        a.download = 'invoice-' + reservationId + '-' + new Date().toISOString().split('T')[0] + '.pdf';
                        console.log('Download filename:', a.download);
                        
                        document.body.appendChild(a);
                        console.log('Link dodat u DOM, klikam...');
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                        console.log('Download pokrenut uspešno');
                        window.downloadInProgress = false;
                        console.log('Resetujem globalni downloadInProgress = false');
                    })
                    .catch(error => {
                        console.error('Greška pri preuzimanju:', error);
                        console.error('Error details:', {
                            message: error.message,
                            stack: error.stack,
                            timestamp: new Date().toISOString()
                        });
                        alert('Greška pri preuzimanju računa. Pokušajte ponovo. Detalji: ' + error.message);
                        window.downloadInProgress = false;
                        console.log('Resetujem globalni downloadInProgress = false (error)');
                    });
            } else {
                console.log('Nema reservationId, ne mogu da pokrenem download');
            }
        }, 2000);
        }
    </script>
</body>
</html>