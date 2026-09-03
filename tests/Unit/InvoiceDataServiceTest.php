<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\UserProduct;
use App\Models\WebsiteOrder;
use App\Models\WebsiteOrderItem;
use App\Services\InvoiceDataService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class InvoiceDataServiceTest extends TestCase
{
    public function test_it_sums_quantities_and_every_money_total_without_using_max(): void
    {
        $product = new Product(['name' => 'Dress', 'barcode' => 'D-1']);
        $product->id = 10;

        $firstItem = $this->orderItem($product, 101, 2, 100, 200, 95.24, 4.76);
        $secondItem = $this->orderItem($product, 102, 3, 100, 300, 95.24, 4.76);

        $order = new Order([
            'type' => Order::TYPE_CASH,
            'curr_type' => 'USD',
            'curr_rate' => 1,
            'sent_at' => now(),
        ]);
        $order->setRelation('items', new Collection([$firstItem, $secondItem]));

        $data = (new InvoiceDataService())->forOrder($order);
        $line = $data['items']->first();

        $this->assertCount(1, $data['items']);
        $this->assertSame(5, $line->qty);
        $this->assertSame(100.0, $line->item_price);
        $this->assertSame(476.2, $line->line_price_without_tax);
        $this->assertSame(23.8, $line->line_tax_value);
        $this->assertSame(500.0, $line->total_price);
    }

    public function test_it_keeps_the_same_product_on_separate_lines_when_unit_prices_differ(): void
    {
        $product = new Product(['name' => 'Dress', 'barcode' => 'D-1']);
        $product->id = 10;
        $order = new Order([
            'type' => Order::TYPE_CASH,
            'curr_type' => 'AED',
            'curr_rate' => 3.67,
            'sent_at' => now(),
        ]);
        $order->setRelation('items', new Collection([
            $this->orderItem($product, 101, 1, 10, 10, 10, 0),
            $this->orderItem($product, 102, 1, 12, 12, 12, 0),
        ]));

        $data = (new InvoiceDataService())->forOrder($order);

        $this->assertCount(2, $data['items']);
        $this->assertSame([36.7, 44.04], $data['items']->pluck('total_price')->all());
    }

    public function test_website_order_values_are_already_local_and_syp_uses_integer_rounding(): void
    {
        $product = new Product(['name' => 'Coat', 'barcode' => 'C-1']);
        $product->id = 20;
        $color = new ProductColor();
        $color->id = 201;
        $color->setRelation('product', $product);

        $item = new WebsiteOrderItem([
            'product_color_id' => 201,
            'qty' => 2,
            'item_price' => 100000.4,
            'total_price' => 200001,
        ]);
        $item->id = 1;
        $item->setRelation('product', $color);

        $order = new WebsiteOrder(['curr_type' => 'SYP', 'curr_rate' => 13000]);
        $order->setRelation('items', new Collection([$item]));

        $data = (new InvoiceDataService())->forOrder($order);
        $line = $data['items']->first();

        $this->assertSame(0, $data['decimals']);
        $this->assertSame(100000.0, $line->item_price);
        $this->assertSame(200001.0, $line->total_price);
    }

    private function orderItem(Product $product, int $colorId, int $qty, float $unit, float $total, float $net, float $tax): OrderItem
    {
        $color = new ProductColor();
        $color->id = $colorId;
        $color->setRelation('product', $product);

        $stock = new UserProduct();
        $stock->id = $colorId;
        $stock->setRelation('productColor', $color);

        $item = new OrderItem([
            'user_product_id' => $colorId,
            'qty' => $qty,
            'item_price' => $unit,
            'total_price' => $total,
            'price_without_tax' => $net,
            'tax_value' => $tax,
        ]);
        $item->id = $colorId;
        $item->setRelation('user_product', $stock);

        return $item;
    }
}
