<?php

// This worker is restricted to the temporary SQLite database passed by the test.
require dirname(__DIR__, 2) . '/vendor/autoload.php';
$app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['app.key' => 'finance-conversion-concurrency-test', 'database.default' => 'conversion_test',
    'database.connections.conversion_test' => ['driver' => 'sqlite', 'database' => $argv[1],
        'prefix' => '', 'foreign_key_constraints' => false]]);
Illuminate\Support\Facades\DB::statement('PRAGMA busy_timeout = 5000');
$report = json_decode(file_get_contents($argv[2]), true, 512, JSON_THROW_ON_ERROR);
file_put_contents($argv[3], 'ready');
$deadline = microtime(true) + 10;
while (!is_file($argv[4])) {
    if (microtime(true) > $deadline) throw new RuntimeException('Other conversion process did not reach the barrier.');
    usleep(10000);
}
echo app(App\Services\UaeFinanceConversion::class)->apply($report), PHP_EOL;
