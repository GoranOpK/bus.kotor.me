<?php
function parseEnv($path)
{
    if (!file_exists($path)) return [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $val = trim($val);
            $val = trim($val, "\"'");
            $env[trim($key)] = $val;
        }
    }
    return $env;
}

function notifyError($subject, $message)
{
    $to = 'bus@kotor.me';
    $headers = 'From: noreply@kotor.me' . "\r\n" .
               'Content-Type: text/plain; charset=utf-8';
    @mail($to, $subject, $message, $headers);
}

$env = parseEnv(__DIR__ . '/.env');

$db_host = $env['DB_HOST'] ?? '127.0.0.1';
$db_name = $env['DB_DATABASE'] ?? 'web_base';
$db_user = $env['DB_USERNAME'] ?? 'root';
$db_pass = $env['DB_PASSWORD'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    notifyError(
        'Greška pri konekciji na bazu',
        "Došlo je do greške pri pokušaju konekcije na bazu podataka:\n" . $e->getMessage()
    );
    exit(1);
}

// 1. KREIRAJ SVE NEDOSTAJUĆE TABELE ZA NAREDNA 3 MESECA
$today = new DateTimeImmutable('today');
for ($i = 0; $i < 90; $i++) {
    $day = $today->add(new DateInterval("P{$i}D"));
    $tableName = $day->format('Ymd');
    // Proveri da li tabela postoji
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
    $stmt->execute([$db_name, $tableName]);
    if ($stmt->fetchColumn() == 0) {
        // Ako ne postoji, kreiraj
        $dateStr = $day->format('Y-m-d');
        try {
            $pdo->exec("CALL CreateTableForDateWithData('$dateStr')");
        } catch (PDOException $e) {
            notifyError(
                "Greška pri kreiranju tabele za $dateStr",
                "Došlo je do greške pri kreiranju tabele $tableName ($dateStr):\n" . $e->getMessage()
            );
        }
    }
}

// 2. BRIŠI SVE TABELE KOJE SU U PROŠLOSTI (manje od danas)
$stmt = $pdo->prepare("SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_name REGEXP '^[0-9]{8}$'");
$stmt->execute([$db_name]);
$allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

<<<<<<< HEAD
echo "Danas je: " . $today->format('Y-m-d') . "\n";
echo "Pronađene tabele: " . implode(', ', $allTables) . "\n";

foreach ($allTables as $table) {
    $tableDate = DateTime::createFromFormat('Ymd', $table);
    if ($tableDate) {
        $tableDateStr = $tableDate->format('Y-m-d');
        $todayStr = $today->format('Y-m-d');
        
        echo "Tabela: $table -> Datum: $tableDateStr, Danas: $todayStr\n";
        
        if ($tableDateStr < $todayStr) {
            $dateStr = $tableDate->format('Y-m-d');
            
            // Proveri da li tabela zaista postoji pre brisanja
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
            $stmt->execute([$db_name, $table]);
            $tableExists = $stmt->fetchColumn() > 0;
            
            if ($tableExists) {
                try {
                    $pdo->exec("CALL DropTableForDate('$dateStr')");
                    echo "✅ Obrisana tabela: $table ($dateStr)\n";
                } catch (PDOException $e) {
                    echo "❌ Greška pri brisanju tabele $table: " . $e->getMessage() . "\n";
                    notifyError(
                        "Greška pri brisanju tabele za $dateStr",
                        "Došlo je do greške pri brisanju tabele $table ($dateStr):\n" . $e->getMessage()
                    );
                }
            } else {
                echo "⏭️  Tabela $table ne postoji, preskačem brisanje\n";
            }
        } else {
            echo "⏭️  Tabela $table ($tableDateStr) nije u prošlosti, preskačem\n";
            
            // Dodatna provera: briši tabele iz budućnosti koje su starije od 7 dana
            $daysDiff = $today->diff($tableDate)->days;
            if ($tableDateStr > $todayStr && $daysDiff > 7) {
                echo "🗑️  Tabela $table je u budućnosti ali starija od 7 dana, brišem\n";
                
                // Proveri da li tabela zaista postoji pre brisanja
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
                $stmt->execute([$db_name, $table]);
                $tableExists = $stmt->fetchColumn() > 0;
                
                if ($tableExists) {
                    try {
                        $pdo->exec("CALL DropTableForDate('$dateStr')");
                        echo "✅ Obrisana tabela iz budućnosti: $table ($dateStr)\n";
                    } catch (PDOException $e) {
                        echo "❌ Greška pri brisanju tabele iz budućnosti $table: " . $e->getMessage() . "\n";
                        notifyError(
                            "Greška pri brisanju tabele iz budućnosti za $dateStr",
                            "Došlo je do greške pri brisanju tabele $table ($dateStr):\n" . $e->getMessage()
                        );
                    }
                } else {
                    echo "⏭️  Tabela $table iz budućnosti ne postoji, preskačem brisanje\n";
                }
            }
        }
    } else {
        echo "⚠️  Ne mogu da parsujem datum iz tabele: $table\n";
=======
foreach ($allTables as $table) {
    $tableDate = DateTime::createFromFormat('Ymd', $table);
    if ($tableDate && $tableDate < $today) {
        $dateStr = $tableDate->format('Y-m-d');
        try {
            $pdo->exec("CALL DropTableForDate('$dateStr')");
        } catch (PDOException $e) {
            notifyError(
                "Greška pri brisanju tabele za $dateStr",
                "Došlo je do greške pri brisanju tabele $table ($dateStr):\n" . $e->getMessage()
            );
        }
>>>>>>> 9d6ee7a59e5e93661c589e783ea991b54a6acabb
    }
}
?>