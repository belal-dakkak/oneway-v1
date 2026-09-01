<?php

namespace Tests\Unit;

use App\Support\Country;
use Tests\TestCase;

class CountryConfigurationTest extends TestCase
{
    public function test_existing_ids_are_preserved_and_syria_uses_id_four(): void
    {
        $this->assertSame(1, Country::id('LB'));
        $this->assertSame(2, Country::id('AE'));
        $this->assertSame(4, Country::id('SY'));
        $this->assertSame(3, Country::globalProductId());
    }

    public function test_syria_defaults_to_syp_and_allows_usd(): void
    {
        $this->assertSame('SYP', Country::defaultCurrency(Country::SYRIA));
        $this->assertSame(['SYP', 'USD'], Country::currencyCodes(Country::SYRIA, true));
        $this->assertSame('Asia/Damascus', Country::timezone(Country::SYRIA));
    }

    public function test_turkey_remains_outside_the_active_storefront(): void
    {
        $this->assertArrayNotHasKey('TR', Country::storefront());
        $this->assertSame([1, 2, 4], Country::allowedIds());
    }
}
