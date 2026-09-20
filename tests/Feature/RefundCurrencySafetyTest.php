<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductColor;
use App\Models\Refund;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\Wallet;
use App\Models\WalletMovement;
use App\Repositories\RefundRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RefundCurrencySafetyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->unsignedInteger('role_id');
            $table->unsignedInteger('country_id');
            $table->timestamps();
        });
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('credit', 20, 4)->default(0);
            $table->decimal('debit', 20, 4)->default(0);
            $table->timestamps();
        });
        Schema::create('wallet_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wallet_id');
            $table->unsignedBigInteger('user_id');
            $table->string('currency_code', 3);
            $table->string('direction', 8);
            $table->decimal('amount', 24, 4);
            $table->decimal('exchange_rate', 20, 6);
            $table->decimal('base_amount', 24, 4);
            $table->decimal('balance_after', 24, 4);
            $table->string('payment_method')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->uuid('exchange_group')->nullable();
            $table->string('idempotency_key')->unique();
            $table->text('note')->nullable();
            $table->timestamps();
        });
        Schema::create('product_colors', function (Blueprint $table) {
            $table->id();
            $table->string('barcode');
            $table->unsignedInteger('country_id');
            $table->timestamps();
        });
        Schema::create('user_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_color_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('country_id');
            $table->string('barcode')->nullable();
            $table->integer('stock')->default(0);
            $table->decimal('wholesale_price', 20, 4)->default(0);
            $table->decimal('retail_price', 20, 4)->default(0);
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->string('barcode');
            $table->unsignedInteger('type');
            $table->string('order_type')->nullable();
            $table->string('curr_type', 8)->default('USD');
            $table->decimal('curr_rate', 20, 6)->default(1);
            $table->decimal('total_price', 20, 4)->default(0);
            $table->decimal('paid_price', 20, 4)->default(0);
            $table->decimal('remain_price', 20, 4)->default(0);
            $table->decimal('shipping_fee', 20, 4)->default(0);
            $table->decimal('cod_fee', 20, 4)->default(0);
            $table->timestamps();
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('user_product_id');
            $table->integer('qty');
            $table->integer('sold_qty')->nullable();
            $table->decimal('unit_cost', 20, 4)->nullable();
            $table->decimal('item_price', 20, 4);
            $table->decimal('total_price', 20, 4);
            $table->decimal('tax_ratio', 8, 4)->default(0);
            $table->decimal('tax_value', 20, 4)->default(0);
            $table->decimal('price_without_tax', 20, 4)->default(0);
            $table->decimal('item_price_paid', 20, 4)->default(0);
            $table->decimal('total_price_paid', 20, 4)->default(0);
            $table->decimal('tax_value_paid', 20, 4)->default(0);
            $table->decimal('price_without_tax_paid', 20, 4)->default(0);
            $table->timestamps();
        });
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_item_id');
            $table->integer('qty');
            $table->decimal('item_price', 20, 4);
            $table->decimal('total_price', 20, 4);
            $table->decimal('total_price_paid', 20, 4)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->decimal('net_amount', 20, 4)->nullable();
            $table->decimal('tax_amount', 20, 4)->nullable();
            $table->decimal('cost_amount', 20, 4)->nullable();
            $table->string('item_barcode');
            $table->string('order_barcode');
            $table->timestamps();
        });
    }

    public function test_refund_uses_the_stored_invoice_price_and_does_not_apply_wholesale_tax_again(): void
    {
        $seller = User::query()->create([
            'name' => 'Syrian shop',
            'email' => 'shop-refund@example.test',
            'password' => 'x',
            'role_id' => User::ROLE_SHOP,
            'country_id' => User::COUNTRY_SYRIA,
        ]);
        $seller->wallet()->update(['credit' => 100, 'debit' => 0]);
        $this->actingAs($seller);

        $color = ProductColor::query()->create([
            'barcode' => 'MODEL-1',
            'country_id' => User::COUNTRY_SYRIA,
        ]);
        $stock = UserProduct::query()->create([
            'product_color_id' => $color->id,
            'user_id' => $seller->id,
            'country_id' => User::COUNTRY_SYRIA,
            'stock' => 0,
            'wholesale_price' => 40,
            'retail_price' => 60,
        ]);
        $order = Order::query()->create([
            'seller_id' => $seller->id,
            'barcode' => 'WHOLESALE-1',
            'type' => Order::TYPE_CASH,
            'order_type' => 'complex_from_multi',
            'curr_type' => 'USD',
            'curr_rate' => 1,
            'total_price' => 120,
            'paid_price' => 120,
            'remain_price' => 0,
            'shipping_fee' => 10,
            'cod_fee' => 5,
        ]);
        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'user_product_id' => $stock->id,
            'qty' => 2,
            'sold_qty' => 2,
            'unit_cost' => 40,
            'item_price' => 52.50,
            'total_price' => 105,
            'tax_ratio' => 5,
            'tax_value' => 2.50,
            'price_without_tax' => 50,
            'item_price_paid' => 52.50,
            'total_price_paid' => 105,
            'tax_value_paid' => 2.50,
            'price_without_tax_paid' => 50,
        ]);

        $request = Request::create('/admin/refunds', 'POST', [
            'selected_products' => [[
                'product_id' => $item->id,
                'qty' => 1,
            ]],
        ]);

        $refund = DB::transaction(function () use ($request) {
            return app(RefundRepository::class)->add($request);
        });

        $this->assertInstanceOf(Refund::class, $refund);
        $this->assertSame(52.5, (float) $refund->item_price);
        $this->assertSame(52.5, (float) $refund->total_price_paid);
        $this->assertSame('USD', $refund->currency_code);
        $this->assertSame(50.0, (float) $refund->net_amount);
        $this->assertSame(2.5, (float) $refund->tax_amount);
        $this->assertSame(40.0, (float) $refund->cost_amount);
        $this->assertSame(1, (int) $item->fresh()->qty);
        $this->assertSame(52.5, (float) $item->fresh()->item_price);
        $this->assertSame(50.0, (float) $item->fresh()->price_without_tax);
        $this->assertSame(2.5, (float) $item->fresh()->tax_value);
        $this->assertSame(52.5, (float) $item->fresh()->total_price);
        $this->assertSame(1, (int) $stock->fresh()->stock);
        $wallet = $seller->wallet->fresh();
        $this->assertSame(100.0, (float) $wallet->credit);
        $this->assertSame(52.5, (float) $wallet->debit);
        $this->assertSame(47.5, (float) $wallet->credit - (float) $wallet->debit);

        $finalRefund = DB::transaction(function () use ($item) {
            return app(RefundRepository::class)->add(Request::create('/admin/refunds', 'POST', [
                'selected_products' => [[
                    'product_id' => $item->id,
                    'qty' => 1,
                ]],
            ]));
        });

        $this->assertSame(67.5, (float) $finalRefund->fresh()->total_price_paid);
        $this->assertSame(65.0, (float) $finalRefund->fresh()->net_amount);
        $this->assertSame(120.0, (float) $order->fresh()->total_price);
        $this->assertSame(10.0, (float) $order->fresh()->shipping_fee);
        $this->assertSame(5.0, (float) $order->fresh()->cod_fee);
        $this->assertSame(120.0, (float) $seller->wallet->fresh()->debit);
    }

    public function test_custom_price_changes_the_cash_refund_but_preserves_the_original_invoice(): void
    {
        [$seller, $stock, $order, $item] = $this->refundFixture();
        $this->actingAs($seller);

        $refund = DB::transaction(function () use ($item) {
            return app(RefundRepository::class)->add(Request::create('/admin/refunds', 'POST', [
                'selected_products' => [['product_id' => $item->id, 'qty' => 1, 'price' => '25.00']],
            ]));
        });

        $this->assertSame(25.0, (float) $refund->total_price_paid);
        $this->assertSame(25.0, (float) $refund->item_price);
        $this->assertSame(1.19, (float) $refund->tax_amount);
        $this->assertSame(23.81, (float) $refund->net_amount);
        $this->assertSame(1, (int) $item->fresh()->qty);
        $this->assertSame(52.5, (float) $item->fresh()->total_price);
        $this->assertSame(120.0, (float) $order->fresh()->total_price);
        $this->assertSame(25.0, (float) $seller->wallet->fresh()->debit);

        $zeroRefund = DB::transaction(function () use ($item) {
            return app(RefundRepository::class)->add(Request::create('/admin/refunds', 'POST', [
                'selected_products' => [['product_id' => $item->id, 'qty' => 1, 'price' => '0']],
            ]));
        });

        $this->assertSame(0.0, (float) $zeroRefund->total_price_paid);
        $this->assertSame(0.0, (float) $zeroRefund->net_amount);
        $this->assertSame(0.0, (float) $zeroRefund->tax_amount);
        $this->assertSame(0, (int) $item->fresh()->qty);
        $this->assertSame(2, (int) $stock->fresh()->stock);
        $this->assertSame(25.0, (float) $seller->wallet->fresh()->debit);
        $this->assertSame(1, WalletMovement::query()->where('payment_method', 'refund')->count());
        $this->assertSame(10.0, (float) $order->fresh()->shipping_fee);
        $this->assertSame(5.0, (float) $order->fresh()->cod_fee);
    }

    public function test_custom_price_is_rejected_above_the_invoice_price_or_with_invalid_precision(): void
    {
        [$seller, $stock, $order, $item] = $this->refundFixture();
        $this->actingAs($seller);

        foreach (['52.51', '-1', '25.001', ''] as $price) {
            try {
                DB::transaction(function () use ($item, $price) {
                    app(RefundRepository::class)->add(Request::create('/admin/refunds', 'POST', [
                        'selected_products' => [['product_id' => $item->id, 'qty' => 1, 'price' => $price]],
                    ]));
                });
                $this->fail("Price {$price} should have been rejected.");
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('selected_products.0.price', $exception->errors());
            }
        }

        $this->assertSame(2, (int) $item->fresh()->qty);
        $this->assertSame(0, (int) $stock->fresh()->stock);
        $this->assertSame(0.0, (float) $seller->wallet->fresh()->debit);
        $this->assertSame(0, Refund::query()->count());
        $this->assertSame(0, WalletMovement::query()->count());
    }

    public function test_syp_refund_price_must_be_an_integer_in_the_invoice_currency(): void
    {
        [$seller, $stock, $order, $item] = $this->refundFixture('SYP', 1000);
        $this->actingAs($seller);

        try {
            app(RefundRepository::class)->add(Request::create('/admin/refunds', 'POST', [
                'selected_products' => [['product_id' => $item->id, 'qty' => 1, 'price' => '25000.50']],
            ]));
            $this->fail('SYP refund price must be an integer.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('selected_products.0.price', $exception->errors());
        }

        $refund = DB::transaction(function () use ($item) {
            return app(RefundRepository::class)->add(Request::create('/admin/refunds', 'POST', [
                'selected_products' => [['product_id' => $item->id, 'qty' => 1, 'price' => '25000']],
            ]));
        });
        $this->assertSame(25000.0, (float) $refund->total_price_paid);
        $this->assertSame(25.0, (float) $refund->total_price);
        $this->assertSame('SYP', $refund->currency_code);
        $this->assertSame(25000.0, (float) Wallet::query()->where('user_id', $seller->id)->where('currency_code', 'SYP')->first()->debit);
    }

    private function refundFixture(string $currency = 'USD', float $rate = 1): array
    {
        $seller = User::query()->create([
            'name' => 'Refund shop', 'email' => uniqid('refund-', true) . '@example.test',
            'password' => 'x', 'role_id' => User::ROLE_SHOP, 'country_id' => User::COUNTRY_SYRIA,
        ]);
        Wallet::query()->updateOrCreate(
            ['user_id' => $seller->id, 'currency_code' => $currency],
            ['credit' => 1000 * $rate, 'debit' => 0]
        );
        $color = ProductColor::query()->create(['barcode' => 'MODEL-2', 'country_id' => User::COUNTRY_SYRIA]);
        $stock = UserProduct::query()->create([
            'product_color_id' => $color->id, 'user_id' => $seller->id,
            'country_id' => User::COUNTRY_SYRIA, 'barcode' => 'STOCK-2', 'stock' => 0,
            'wholesale_price' => 40, 'retail_price' => 60,
        ]);
        $order = Order::query()->create([
            'seller_id' => $seller->id, 'barcode' => 'REFUND-FIXTURE', 'type' => Order::TYPE_CASH,
            'curr_type' => $currency, 'curr_rate' => $rate,
            'total_price' => 120 * $rate, 'paid_price' => 120 * $rate, 'remain_price' => 0,
            'shipping_fee' => 10, 'cod_fee' => 5,
        ]);
        $item = OrderItem::query()->create([
            'order_id' => $order->id, 'user_product_id' => $stock->id, 'qty' => 2,
            'sold_qty' => 2, 'unit_cost' => 40, 'item_price' => 52.5, 'total_price' => 105,
            'tax_ratio' => 5, 'tax_value' => 2.5, 'price_without_tax' => 50,
            'item_price_paid' => 52.5 * $rate, 'total_price_paid' => 105 * $rate,
            'tax_value_paid' => 2.5 * $rate, 'price_without_tax_paid' => 50 * $rate,
        ]);

        return [$seller, $stock, $order, $item];
    }
}
