<?php

namespace Tests\Unit;

use App\Support\SyriaGovernorates;
use PHPUnit\Framework\TestCase;

class SyriaGovernoratesTest extends TestCase
{
    public function test_the_checkout_allowlist_contains_the_fourteen_governorates(): void
    {
        $this->assertCount(14, SyriaGovernorates::all());
        $this->assertContains('دمشق', SyriaGovernorates::all());
        $this->assertContains('ريف دمشق', SyriaGovernorates::all());
        $this->assertContains('الحسكة', SyriaGovernorates::all());
        $this->assertSame(SyriaGovernorates::all(), array_values(array_unique(SyriaGovernorates::all())));
    }
}
