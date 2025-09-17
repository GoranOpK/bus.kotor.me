<?php
// cron_schedule.php
// Ovaj fajl pokreće Laravel scheduler kao da si pozvao 'php artisan schedule:run'

// Prilagodi putanju do PHP binarija ako treba:
$php = '/opt/plesk/php/8.3/bin/php';

// Prilagodi putanju do artisan fajla ako treba:
$artisan = __DIR__ . '/artisan';

// Pokreni komandu
passthru("$php $artisan schedule:run"); 