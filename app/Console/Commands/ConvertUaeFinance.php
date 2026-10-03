<?php

namespace App\Console\Commands;

use App\Services\UaeFinanceConversion;
use App\Support\LegacyUaeDebtSnapshot;
use Illuminate\Console\Command;
use RuntimeException;

class ConvertUaeFinance extends Command
{
    protected $signature = 'finance:convert-uae-to-aed
        {--source= : Original client_debits.sql export before label correction}
        {--output= : Private JSON report path (defaults to storage/app/uae-finance-report.json)}
        {--apply= : Apply a previously generated, unchanged report while the app is in maintenance}';
    protected $description = 'Audit or convert proven UAE financial amounts to AED at 3.675, preserving USD base prices.';

    public function handle(UaeFinanceConversion $service, LegacyUaeDebtSnapshot $reader): int
    {
        try {
            if ($path = $this->option('apply')) {
                if (!app()->isDownForMaintenance()) throw new RuntimeException('Pause writes first with php artisan down; resume with php artisan up after verification.');
                $path = $this->privatePath($path, true);
                $report = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
                $this->info($service->apply($report));
                return 0;
            }
            $legacy = $this->option('source') ? $reader->read($this->option('source')) : null;
            $report = $service->report($legacy);
            $path = $this->privatePath($this->option('output') ?: storage_path('app/uae-finance-report.json'));
            if (file_exists($path)) throw new RuntimeException('Report already exists; choose another --output path.');
            if (file_put_contents($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR), LOCK_EX) === false) {
                throw new RuntimeException('Could not save report.');
            }
            @chmod($path, 0600);
            $this->info('Preview only: ' . count($report['changes']) . ' field changes; ' . count($report['conflicts']) . ' conflicts.');
            $this->line('Report: ' . $path);
            $this->table(['Table', 'ID', 'Reason'], array_map(fn ($row) => array_values($row), $report['conflicts']));
            return $report['conflicts'] ? 1 : 0;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
            return 1;
        }
    }

    private function privatePath(string $path, bool $existing = false): string
    {
        $parent = realpath(dirname($path));
        if (!$parent || ($existing && !is_file($path))) throw new RuntimeException('Report directory or file does not exist.');
        $resolved = realpath($path) ?: $parent . DIRECTORY_SEPARATOR . basename($path);
        $public = str_replace('\\', '/', realpath(public_path()));
        if (strpos(strtolower(str_replace('\\', '/', $resolved)) . '/', strtolower($public) . '/') === 0) {
            throw new RuntimeException('Store financial reports outside the public directory.');
        }
        return $resolved;
    }
}
