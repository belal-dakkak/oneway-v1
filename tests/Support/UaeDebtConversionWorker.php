<?php

// Isolated process used by the concurrency test; never connects to the configured live database.
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['database.default' => 'conversion_test', 'database.connections.conversion_test' => [
    'driver' => 'sqlite', 'database' => $argv[1], 'prefix' => '', 'foreign_key_constraints' => false,
]]);
Illuminate\Support\Facades\DB::statement('PRAGMA busy_timeout = 1000');
$firstRead = true;
Illuminate\Support\Facades\DB::listen(function ($query) use (&$firstRead, $argv) {
    if (!$firstRead || strpos($query->sql, 'from "client_debits"') === false) return;
    $firstRead = false;
    file_put_contents($argv[3], 'ready');
    $deadline = microtime(true) + 10;
    while (!is_file($argv[4])) {
        if (microtime(true) > $deadline) throw new RuntimeException('Concurrent reader did not reach the barrier.');
        usleep(10000);
    }
});
$snapshot = (new App\Support\LegacyUaeDebtSnapshot)->read($argv[2]);
$report = (new App\Services\UaeDebtBalanceConversion)->convert($snapshot, true);
echo json_encode($report['counts']), PHP_EOL;
