<?php

namespace App\Console\Commands;

use App\Services\UnpaidOrdersBaselineService;
use Illuminate\Console\Command;
use RuntimeException;

class BaselineUnpaidOrders extends Command
{
    protected $signature = 'orders:baseline-unpaid
        {--output= : Private JSON preview report path}
        {--apply= : Apply a previously generated and unchanged report}';

    protected $description = 'Preview or archive the current unpaid-orders tab without changing financial balances.';

    public function handle(UnpaidOrdersBaselineService $service): int
    {
        try {
            if ($this->option('output') && $this->option('apply')) {
                throw new RuntimeException('Use either --output or --apply, not both.');
            }

            if ($apply = $this->option('apply')) {
                $path = $this->privatePath((string) $apply, true);
                $report = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
                $result = $service->apply($report, auth()->id() ?: null);
                $this->info("Archived {$result['archived_orders_count']} unpaid orders; {$result['already_applied_orders_count']} were already archived.");
                $this->warn('Order amounts, customer balances, payments, and cashboxes were not changed.');
                return 0;
            }

            $report = $service->report();
            $default = storage_path('app/unpaid-orders-baseline-' . now()->format('Ymd-His') . '.json');
            $path = $this->privatePath((string) ($this->option('output') ?: $default));
            if (file_exists($path)) {
                throw new RuntimeException('Report already exists; choose another --output path.');
            }

            $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
            if (file_put_contents($path, $json, LOCK_EX) === false) {
                throw new RuntimeException('Could not save the unpaid-orders baseline report.');
            }
            @chmod($path, 0600);

            $this->info("Preview only: {$report['orders_count']} currently visible unpaid orders will be archived.");
            foreach ($report['remaining_total_by_currency'] as $currency => $total) {
                $this->line("{$currency}: {$total}");
            }
            $this->line('Report: ' . $path);
            $this->line('Review the report, then apply it with --apply=' . $path);
            return 0;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return 1;
        }
    }

    private function privatePath(string $path, bool $existing = false): string
    {
        if (!$this->isAbsolute($path)) {
            $path = base_path($path);
        }
        $parent = realpath(dirname($path));
        if (!$parent || ($existing && !is_file($path))) {
            throw new RuntimeException('Report directory or file does not exist.');
        }
        $resolved = realpath($path) ?: $parent . DIRECTORY_SEPARATOR . basename($path);
        $public = str_replace('\\', '/', (string) realpath(public_path()));
        $normalized = str_replace('\\', '/', $resolved);
        if ($public !== '' && strpos(strtolower($normalized) . '/', strtolower($public) . '/') === 0) {
            throw new RuntimeException('Store financial reports outside the public directory.');
        }
        return $resolved;
    }

    private function isAbsolute(string $path): bool
    {
        return $path !== '' && ($path[0] === '/' || $path[0] === '\\' || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1);
    }
}
