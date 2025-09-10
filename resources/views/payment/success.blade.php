<!DOCTYPE html>
<html>
<head>
    <title>Plaćanje uspješno</title>
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
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="success-container">
        @if(isset($waitingForReservation) && $waitingForReservation)
            <div class="success-icon">⏳</div>
            <h1>Plaćanje uspješno!</h1>
            <p>{{ $message ?? 'Vaša uplata je primljena. Molimo sačekajte da se rezervacija završi...' }}</p>
            
            <div class="info">
                <p><strong>Molimo sačekajte:</strong> Vaša rezervacija se trenutno obrađuje.</p>
                <p>Ova stranica će se automatski osvežiti kada bude spremna.</p>
            </div>
            
            <div id="loading-spinner" style="margin: 20px 0;">
                <div style="border: 4px solid #f3f3f3; border-top: 4px solid #007bff; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
            </div>
            
            <a href="/" class="home-btn">
                Povratak na početnu
            </a>
        @else
            <div class="success-icon">✓</div>
            <h1>Plaćanje uspješno!</h1>
            <p>Vaša uplata je primljena. Rezervacija je zabilježena.</p>
            
            <div class="info">
                <p><strong>Važno:</strong> PDF račun će biti automatski preuzet za nekoliko sekundi.</p>
                <p>Ukoliko se preuzimanje ne pokrene automatski, koristite dugme ispod.</p>
            </div>

            <a href="/download-invoice/{{ $reservationId ?? '' }}" class="download-btn" style="display:inline-block;margin-top:20px;">
                📄 Preuzmi račun (PDF)
            </a>
            
            <a href="/" class="home-btn">
                Povratak na početnu
            </a>
        @endif
    </div>

    <script>
        @if(isset($waitingForReservation) && $waitingForReservation)
            // Automatsko osvežavanje stranice dok se rezervacija ne završi
            var merchantTransactionId = "{{ $merchantTransactionId ?? '' }}";
            var checkInterval = setInterval(function() {
                console.log('Proveravam da li je rezervacija završena...');
                
                // Proveri da li postoji rezervacija u bazi
                fetch('/api/check-reservation/' + merchantTransactionId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            console.log('Rezervacija pronađena, osvežavam stranicu...');
                            clearInterval(checkInterval);
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        console.log('Greška pri proveri rezervacije:', error);
                        // Fallback: pokušaj da osvežiš stranicu direktno
                        console.log('Pokušavam fallback osvežavanje...');
                        setTimeout(function() {
                            window.location.reload();
                        }, 5000); // Osveži za 5 sekundi ako API ne radi
                    });
            }, 2000); // Proveri svake 2 sekunde
            
            // Zaustavi proveru nakon 60 sekundi
            setTimeout(function() {
                clearInterval(checkInterval);
                console.log('Provera zaustavljena nakon 60 sekundi');
                // Finalni fallback: osveži stranicu
                window.location.reload();
            }, 60000);
        @else
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
        @endif
    </script>
</body>
</html>