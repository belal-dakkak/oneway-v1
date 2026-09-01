<?php

namespace Tests\Unit;

use App\Services\TaxCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TaxCalculatorTest extends TestCase
{
    public function test_simple_and_client_prices_are_tax_inclusive(): void
    {
        $calculator = new TaxCalculator();

        foreach (['simple', 'complex'] as $type) {
            $result = $calculator->calculate(100, 5, $type);

            $this->assertEqualsWithDelta(95.2381, $result['price_without_tax'], 0.0001);
            $this->assertEqualsWithDelta(4.7619, $result['tax_value'], 0.0001);
            $this->assertEqualsWithDelta(100, $result['price_with_tax'], 0.0001);
            $this->assertSame('inclusive', $result['mode']);
        }
    }

    public function test_wholesale_price_is_tax_exclusive(): void
    {
        $result = (new TaxCalculator())->calculate(50, 5, 'complex_from_multi');

        $this->assertEqualsWithDelta(50, $result['price_without_tax'], 0.0001);
        $this->assertEqualsWithDelta(2.5, $result['tax_value'], 0.0001);
        $this->assertEqualsWithDelta(52.5, $result['price_with_tax'], 0.0001);
        $this->assertSame('exclusive', $result['mode']);
    }

    public function test_disabled_tax_keeps_the_entered_price_for_every_order_type(): void
    {
        $calculator = new TaxCalculator();

        foreach (['simple', 'complex', 'complex_from_multi'] as $type) {
            $result = $calculator->calculate(123.45, 0, $type);
            $this->assertSame(123.45, $result['price_without_tax']);
            $this->assertSame(0.0, $result['tax_value']);
            $this->assertSame(123.45, $result['price_with_tax']);
        }
    }

    public function test_unknown_order_type_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TaxCalculator())->calculate(100, 5, 'website');
    }
}
