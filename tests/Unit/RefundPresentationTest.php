<?php

namespace Tests\Unit;

use App\Models\Refund;
use PHPUnit\Framework\TestCase;

class RefundPresentationTest extends TestCase
{
    public function test_historical_refund_with_missing_relations_can_be_serialized(): void
    {
        $refund = new Refund([
            'qty' => 1,
            'total_price' => 10,
            'currency_code' => 'USD',
            'item_barcode' => 'MISSING-ITEM',
            'order_barcode' => 'MISSING-ORDER',
        ]);
        $refund->setRelation('orderItem', null);

        $data = $refund->toArray();

        $this->assertSame('—', $data['shop_name']);
        $this->assertSame('—', $data['item_name']);
        $this->assertSame('', $data['item_image']);
        $this->assertSame('طلبية سريعة', $data['client_name']);
        $this->assertSame('USD', $data['currency_code']);
    }
}
