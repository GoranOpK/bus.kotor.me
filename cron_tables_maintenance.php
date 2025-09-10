<?php
// Postavi timezone na lokalno vreme
date_default_timezone_set('Europe/Berlin');

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

foreach ($allTables as $table) {
    $tableDate = DateTime::createFromFormat('Ymd', $table);
    if ($tableDate) {
        $tableDateStr = $tableDate->format('Y-m-d');
        $todayStr = $today->format('Y-m-d');
        
        if ($tableDateStr < $todayStr) {
            $dateStr = $tableDate->format('Y-m-d');
            
            // Proveri da li tabela zaista postoji pre brisanja
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
            $stmt->execute([$db_name, $table]);
            $tableExists = $stmt->fetchColumn() > 0;
            
            if ($tableExists) {
                try {
                    // Prvo pokušaj sa stored procedure
                    $pdo->exec("CALL DropTableForDate('$dateStr')");
                    
                    // Proveri da li je tabela zaista obrisana
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
                    $stmt->execute([$db_name, $table]);
                    $tableStillExists = $stmt->fetchColumn() > 0;
                    
                    if ($tableStillExists) {
                        notifyError(
                            "Tabela nije obrisana",
                            "Tabela $table ($dateStr) nije obrisana nakon poziva DropTableForDate procedure."
                        );
                    }
                } catch (PDOException $e) {
                    // Ako stored procedure ne radi, pokušaj direktno DROP
                    if (strpos($e->getMessage(), '1644') !== false || strpos($e->getMessage(), 'Tabela nije kreirana') !== false) {
                        try {
                            $pdo->exec("DROP TABLE IF EXISTS `$table`");
                            
                            // Proveri da li je tabela zaista obrisana
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
                            $stmt->execute([$db_name, $table]);
                            $tableStillExists = $stmt->fetchColumn() > 0;
                            
                            if ($tableStillExists) {
                                notifyError(
                                    "Tabela nije obrisana direktno",
                                    "Tabela $table ($dateStr) nije obrisana ni nakon direktnog DROP TABLE."
                                );
                            }
                        } catch (PDOException $e2) {
                            notifyError(
                                "Greška pri direktnom brisanju tabele za $dateStr",
                                "Došlo je do greške pri direktnom brisanju tabele $table ($dateStr):\n" . $e2->getMessage()
                            );
                        }
                    } else {
                        notifyError(
                            "Greška pri brisanju tabele za $dateStr",
                            "Došlo je do greške pri brisanju tabele $table ($dateStr):\n" . $e->getMessage()
                        );
                    }
                }
            }
        }
    }
}
?>