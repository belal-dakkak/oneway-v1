<?php

namespace Tests\Unit;

use App\Support\LegacyUaeDebtSnapshot;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LegacyUaeDebtSnapshotTest extends TestCase
{
    private function read(string $rows, string $suffix = ''): array
    {
        $path = tempnam(sys_get_temp_dir(), 'uae-snapshot-');
        try {
            file_put_contents($path, 'INSERT INTO `d` (`shop_id`, `customer_id`, `accounts`, `usd_labelled_amount`, `aed_amount`) VALUES '
                . $rows . ';' . $suffix);
            return (new LegacyUaeDebtSnapshot)->read($path);
        } finally {
            unlink($path);
        }
    }

    public function test_reads_exact_decimals_and_account_ids_without_executing_surrounding_sql(): void
    {
        $result = $this->read("(805, 308, '1872: USD = 32.6500', 32.6500, 0.0000),\n(805, 309, '1873: AED = 15.2500', 0, 15.25)",
            '\nDROP TABLE client_debits;');
        $this->assertCount(2, $result['accounts']);
        $this->assertSame(['account_id' => 1872, 'shop_id' => 805, 'customer_id' => 308,
            'source_currency' => 'USD', 'source_amount' => '32.6500'], $result['accounts'][0]);
        $this->assertSame('AED', $result['accounts'][1]['source_currency']);
        $this->assertSame(64, strlen($result['sha256']));
    }

    public function test_rejects_a_partially_truncated_second_insert_instead_of_using_only_the_first_batch(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->read("(805, 308, '1872: USD = 32.6500', 32.65, 0)",
            ' INSERT INTO `d` (`shop_id`, `customer_id`, `accounts`, `usd_labelled_amount`, `aed_amount`) VALUES (805,');
    }

    /** @dataProvider invalidRows */
    public function test_rejects_ambiguous_or_inconsistent_exports(string $rows): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->read($rows);
    }

    public static function invalidRows(): array
    {
        return [
            ["(805, 308, '1872: USD = 32.6500', 32.64, 0)"],
            ["(805, 308, '1872: USD = 32.6500, 1873: USD = 2.0000', 34.65, 0)"],
            ["(805, 308, '1872: USD = 32.6500', 32.65, 0), (805, 309, '1872: USD = 32.6500', 32.65, 0)"],
            ["(805, 308, '1872: USD = 32.6500', 32.65, 0), (805, 308, '1873: AED = 1.0000', 0, 1)"],
            ["(805, 308, '1872: USD = 32.65001', 32.65001, 0)"],
            ["(805, 308, '1872: USD = 32.6500', 32.65, 0), BROKEN"],
            [''],
        ];
    }
}
