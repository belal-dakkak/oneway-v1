<?php

namespace Tests\Unit;

use App\Services\UaeFinanceConversion;
use PHPUnit\Framework\TestCase;

class UaeFinanceCurrencyEvidenceTest extends TestCase
{
    /** @dataProvider decimalEvidence */
    public function test_mysql_decimal_metadata_matches_numeric_storage(array $row, bool $expected): void
    {
        $method = new \ReflectionMethod(UaeFinanceConversion::class, 'usdPayment');
        $method->setAccessible(true);
        // Exercise the same guard used for invoice payments, customer logs and payments.
        foreach (['amount', 'pay_amount'] as $field) {
            $input = $row + [$field => '19.0500'];
            $this->assertSame($expected, $method->invoke(new UaeFinanceConversion, $input, $field));
        }
    }

    public static function decimalEvidence(): array
    {
        return [
            'reported MySQL zero base' => [['exchange_rate' => '1.000000', 'base_amount' => '0.0000'], true],
            'SQLite zero base' => [['exchange_rate' => 1, 'base_amount' => 0], true],
            'negative decimal zero' => [['exchange_rate' => '1.000000', 'base_amount' => '-0.0000'], true],
            'missing old metadata' => [[], true],
            'null old metadata' => [['exchange_rate' => null, 'base_amount' => null], true],
            'explicit matching USD base' => [['exchange_rate' => '1.000000', 'base_amount' => '19.0500'], true],
            'different nonzero base remains a conflict' => [['exchange_rate' => '1.000000', 'base_amount' => '5.1837'], false],
            'AED rate with zero base remains a conflict' => [['exchange_rate' => '3.675000', 'base_amount' => '0.0000'], false],
            'tiny nonzero base is not rounded away' => [['exchange_rate' => '1.000000', 'base_amount' => '0.0001'], false],
        ];
    }
}
