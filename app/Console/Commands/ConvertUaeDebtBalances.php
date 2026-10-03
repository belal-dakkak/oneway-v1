<?php

namespace App\Console\Commands;

use App\Services\UaeDebtBalanceConversion;
use App\Support\LegacyUaeDebtSnapshot;
use Illuminate\Console\Command;

class ConvertUaeDebtBalances extends Command
{
    protected $signature = 'clients:convert-uae-debt-balances
        {--source= : Original client_debits.sql query export, before label correction}
        {--apply : Convert verified remaining balances at 3.675 AED per USD}';
    protected $description = 'Preview or convert legacy UAE remaining debt balances; preserve historical payments and invoices.';

    public function handle(LegacyUaeDebtSnapshot $reader, UaeDebtBalanceConversion $service): int
    {
        if (!$this->option('source')) {
            $this->error('Provide --source=/private/path/client_debits.sql (the original export).');
            return 1;
        }
        try {
            $snapshot = $reader->read((string) $this->option('source'));
            $report = $service->convert($snapshot, (bool) $this->option('apply'));
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
            return 1;
        }
        $this->info('Source SHA256: ' . $report['source_sha256']);
        $this->info('Fixed rate: 1 USD = ' . $report['rate'] . ' AED. Remaining balances only.');
        $this->table(['Account', 'Shop', 'Customer', 'Old USD', 'Current', 'Target AED', 'Adjustment AED', 'Status / reason'],
            collect($report['rows'])->reject(fn ($row) => in_array($row['status'], ['excluded_aed', 'zero_unchanged'], true))
                ->map(fn ($row) => [$row['account_id'], $row['shop_id'], $row['customer_id'], $row['source_amount'],
                    $row['current_amount'], $row['target_amount'], $row['adjustment'], $row['status'] . ' ' . $row['reason']])->all());
        foreach ($report['counts'] as $status => $count) $this->line($status . ': ' . $count);
        if ($report['blocked']) {
            $this->error('Conflicts found. No balances or logs were changed.');
            return 1;
        }
        $this->info($report['applied'] ? 'Completed. Existing invoices, payments and cashboxes were not changed.'
            : 'Preview only. Run the same command with --apply to perform the correction.');
        return 0;
    }
}
