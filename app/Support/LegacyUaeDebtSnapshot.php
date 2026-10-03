<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use InvalidArgumentException;

class LegacyUaeDebtSnapshot
{
    /** Read the old phpMyAdmin query export as data. Never execute its SQL. */
    public function read(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException('Source file is missing or unreadable.');
        }
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new InvalidArgumentException('Unable to read source file.');
        }
        $statement = '/INSERT\s+INTO\s+`d`\s*\(\s*`shop_id`\s*,\s*`customer_id`\s*,\s*`accounts`\s*,\s*`usd_labelled_amount`\s*,\s*`aed_amount`\s*\)\s*VALUES\s*(.*?);/s';
        if (!preg_match_all($statement, $sql, $blocks)) {
            throw new InvalidArgumentException('Expected the original client_debits query export with account IDs and USD/AED balances.');
        }
        if (preg_match_all('/INSERT\s+INTO\s+`?d`?\s*\(/', $sql) !== count($blocks[0])) {
            throw new InvalidArgumentException('An account INSERT is incomplete or uses unsupported columns.');
        }
        $decimal = '-?\d{1,16}(?:\.\d{1,4})?';
        $tuple = "/\\G\\s*\\(\\s*(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*'([^']*)'\\s*,\\s*($decimal)\\s*,\\s*($decimal)\\s*\\)\\s*(?:,\\s*(?=\\()|$)/";
        $accounts = [];
        $pairs = [];
        foreach ($blocks[1] as $body) {
            $offset = 0;
            while ($offset < strlen($body)) {
                if (!preg_match($tuple, $body, $row, 0, $offset)
                    || !preg_match('/^(\d+): (USD|AED) = (' . $decimal . ')$/D', $row[3], $entry)) {
                    throw new InvalidArgumentException('Unsupported or ambiguous account row in the source; nothing was changed.');
                }
                $offset += strlen($row[0]);
                $shop = $this->id($row[1]);
                $customer = $this->id($row[2]);
                $id = $this->id($entry[1]);
                $pair = $shop . ':' . $customer;
                if (isset($accounts[$id]) || isset($pairs[$pair])) {
                    throw new InvalidArgumentException('Duplicate account or shop/customer pair in source: ' . $id);
                }
                $amount = BigDecimal::of($entry[3])->toScale(4);
                $usd = BigDecimal::of($row[4]);
                $aed = BigDecimal::of($row[5]);
                if (($entry[2] === 'USD' && (!$usd->isEqualTo($amount) || !$aed->isZero()))
                    || ($entry[2] === 'AED' && (!$aed->isEqualTo($amount) || !$usd->isZero()))) {
                    throw new InvalidArgumentException('Source totals do not match account ' . $id);
                }
                $accounts[$id] = ['account_id' => $id, 'shop_id' => $shop, 'customer_id' => $customer,
                    'source_currency' => $entry[2], 'source_amount' => (string) $amount];
                $pairs[$pair] = true;
            }
        }
        if (!$accounts) {
            throw new InvalidArgumentException('The source contains no account rows.');
        }
        ksort($accounts);
        return ['sha256' => hash('sha256', $sql), 'accounts' => array_values($accounts)];
    }

    private function id(string $value): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id === false) {
            throw new InvalidArgumentException('Invalid account, shop or customer ID.');
        }
        return $id;
    }
}
