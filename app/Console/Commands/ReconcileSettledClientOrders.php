<?php

namespace App\Console\Commands;

use App\Services\SettledClientOrdersReconciliation;
use Illuminate\Console\Command;
use RuntimeException;

class ReconcileSettledClientOrders extends Command
{
    protected $signature = 'clients:reconcile-settled-orders
        {--output= : Private JSON preview report path}
        {--apply= : Apply a previously generated and unchanged report}';

    protected $description = 'Preview or reconcile outstanding orders whose authoritative customer account is zero or credit.';

    public function handle(SettledClientOrdersReconciliation $service): int
    {
        try {
            if ($apply = $this->option('apply')) {
                $path = $this->privatePath((string) $apply, true);
                $report = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
                $result = $service->apply($report, auth()->id() ?: null);
                $this->info("Reconciled {$result['settled_orders_count']} orders; {$result['already_applied_orders_count']} were already reconciled.");
                foreach ($result['reconciliation_total_by_currency'] as $currency => $total) {
                    $this->line("{$currency}: {$total}");
                }
                $this->warn('No cashbox movement or cash payment was created for reconciliation differences.');
                return 0;
            }

            $report = $service->report();
            $default = storage_path('app/client-debt-reconciliation-' . now()->format('Ymd-His') . '.json');
            $path = $this->privatePath((string) ($this->option('output') ?: $default));
            if (file_exists($path)) {
                throw new RuntimeException('Report already exists; choose another --output path.');
            }
            $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
            if (file_put_contents($path, $json, LOCK_EX) === false) {
                throw new RuntimeException('Could not save reconciliation report.');
            }
            @chmod($path, 0600);

            $this->info("Preview only: {$report['accounts_count']} accounts and {$report['orders_count']} orders require reconciliation.");
            $this->line("Warnings: {$report['warnings_count']}; conflicts: {$report['conflicts_count']}.");
            $this->line('Report: ' . $path);
            foreach ($report['reconciliation_total_by_currency'] as $currency => $total) {
                $this->line("{$currency}: {$total}");
            }
            $this->line('Review the report, then apply it with --apply=' . $path);
            if ($report['conflicts_count'] > 0) {
                $this->error('Resolve ambiguous customer accounts and generate a new report before applying.');
                return 1;
            }
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
