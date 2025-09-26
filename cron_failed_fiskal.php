<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\FiskalController;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$failed = DB::table('failed_fiskal')->get();

$fiskalController = app(FiskalController::class);

foreach ($failed as $row) {
    // Pokušaj fiskalizaciju ponovo
    $result = $fiskalController->fiscalization($row->merchant_transaction_id);

    if ($result['success']) {
        // Pošalji email o uspehu
        Mail::raw(
            "Uspešna fiskalizacija nakon ponovnog pokušaja\n\nPodaci:\n" . print_r($result['data'], true),
            function ($message) use ($row) {
                $message->to('bus@kotor.me')
                    ->subject('Uspešna fiskalizacija (retry)');
            }
        );
        // Obriši zapis
        DB::table('failed_fiskal')->where('id', $row->id)->delete();
    } else {
        // Povećaj broj pokušaja
        DB::table('failed_fiskal')->where('id', $row->id)->update(['attempts' => $row->attempts + 1]);
        // Ako je attempts >= 24, pošalji upozorenje
        if ($row->attempts + 1 >= 24) {
            Mail::raw(
                "Fiskalizacija NIJE uspela ni posle 24 pokušaja!\n\nPodaci:\n" . print_r($row, true),
                function ($message) use ($row) {
                    $message->to('bus@kotor.me')
                        ->subject('UPOZORENJE: Fiskalizacija nije uspela ni posle 24 pokušaja!');
                }
            );
        }
    }
}

echo "Cron finished.\n";