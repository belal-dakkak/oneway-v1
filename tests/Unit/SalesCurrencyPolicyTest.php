<?php

namespace Tests\Unit;

use App\Services\CurrencyService;
use App\Services\SalesCurrencyPolicy;
use App\Support\Country;
use Mockery;
use Tests\TestCase;

class SalesCurrencyPolicyTest extends TestCase
{
    public function test_syrian_dashboard_currency_is_forced_by_order_type(): void
    {
        $currencies = Mockery::mock(CurrencyService::class);
        $currencies->shouldReceive('rate')->with('SYP')->times(4)->andReturn(13000.0);
        $currencies->shouldReceive('rate')->with('USD')->twice()->andReturn(1.0);
        $policy = new SalesCurrencyPolicy($currencies);

        $this->assertSame('SYP', $policy->orderOption(Country::SYRIA, 'simple', 'USD')['code']);
        $this->assertSame('SYP', $policy->orderOption(Country::SYRIA, 'complex', 'USD')['code']);
        $this->assertSame('USD', $policy->orderOption(Country::SYRIA, 'complex_from_multi', 'SYP')['code']);
    }

    public function test_syrian_website_currency_is_syp_for_retail_and_usd_for_wholesale(): void
    {
        $currencies = Mockery::mock(CurrencyService::class);
        $currencies->shouldReceive('rate')->with('SYP')->twice()->andReturn(13000.0);
        $currencies->shouldReceive('rate')->with('USD')->twice()->andReturn(1.0);
        $policy = new SalesCurrencyPolicy($currencies);

        $retail = $policy->websiteOption(Country::SYRIA, false, 'USD');
        $wholesale = $policy->websiteOption(Country::SYRIA, true, 'SYP');

        $this->assertSame(['SYP', 13000.0, 0, true], [
            $retail['code'], $retail['rate'], $retail['decimals'], $retail['locked'],
        ]);
        $this->assertSame(['USD', 1.0, 2, true], [
            $wholesale['code'], $wholesale['rate'], $wholesale['decimals'], $wholesale['locked'],
        ]);
    }
}
