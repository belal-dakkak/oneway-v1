<?php

namespace App\Console\Commands;

use App\Services\LegacyUaeDebtAudit;
use Illuminate\Console\Command;

class AuditLegacyUaeDebts extends Command
{
    protected $signature = 'clients:audit-uae-debts {--output= : Save the read-only report as JSON} {--apply= : Apply a reviewed report; only proven AED labels are eligible}';
    protected $description = 'Audit legacy UAE USD-labelled debts against orders and historical payments.';

    public function handle(LegacyUaeDebtAudit $audit): int
    {
        if ($path = $this->option('apply')) {
            $rows = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            foreach ($rows as $row) {
                if (($row['status'] ?? '') === 'proven_aed_label') {
                    $this->line($row['account_id'] . ': ' . $audit->apply($row));
                }
            }
            return 0;
        }
        $report = json_encode($audit->report(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if ($path = $this->option('output')) {
            file_put_contents($path, $report . PHP_EOL);
            $this->info('Read-only report written to ' . $path);
        } else {
            $this->line($report);
        }
        return 0;
    }
}
