<?php

namespace Tests\Unit;

use App\Rules\ValidPhone;
use App\Services\CurrencyService;
use PHPUnit\Framework\TestCase;

class CurrencyAndPhoneTest extends TestCase
{
    public function test_usd_and_syp_rounding_rules(): void
    {
        $service = new CurrencyService();

        $this->assertSame(123.46, $service->round(123.456, 'USD'));
        $this->assertSame(123.0, $service->round(123.456, 'SYP'));
        $this->assertSame(1.0, $service->rate('USD'));
    }

    public function test_usd_syp_conversion_and_large_amounts(): void
    {
        $service = new CurrencyService();

        $this->assertSame(1300000.0, $service->fromUsdAtRate(100, 13000, 'SYP'));
        $this->assertSame(100.0, $service->toUsdAtRate(1_300_000, 13000));
        $this->assertSame(13000000000.0, $service->fromUsdAtRate(1000000, 13000, 'SYP'));
    }

    public function test_syrian_checkout_accepts_only_a_syrian_mobile_number(): void
    {
        $rule = new ValidPhone('SY');

        $this->assertTrue($rule->passes('phone', '+963 944 123 456'));
        $this->assertFalse($rule->passes('phone', '+971 50 123 4567'));
    }
}
