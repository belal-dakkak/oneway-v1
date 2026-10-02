<?php

namespace App\Console\Commands;

use App\Services\UaeClientCurrencyLabels;
use Illuminate\Console\Command;

class CorrectUaeClientCurrencyLabels extends Command
{
    protected $signature = 'clients:correct-uae-currency-labels {--apply : Change USD labels to AED without changing any numbers}';
    protected $description = 'Correct legacy UAE customer account currency labels; keep every stored amount unchanged.';

    public function handle(UaeClientCurrencyLabels $service): int
    {
        try {
            $result = $service->correct((bool) $this->option('apply'));
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());
            return 1;
        }
        $this->info(($result['applied'] ? 'Updated' : 'Preview') . ': ' . $result['accounts']
            . ' accounts, ' . $result['logs'] . ' log labels. Amounts are unchanged.');
        return 0;
    }
}
