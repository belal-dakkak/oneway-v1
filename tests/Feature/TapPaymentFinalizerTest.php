<?php

namespace Tests\Feature;

use App\Models\UserProduct;
use App\Models\Order;
use App\Models\WebsiteOrder;
use App\Models\WebsiteOrderItem;
use App\Services\Payment\TapPaymentFinalizer;
use App\Services\Payment\TapPaymentResult;
use App\Services\Payment\TapPaymentService;
use App\Services\Payment\WebsiteOrderStockService;
use App\Services\InvoiceDataService;
use App\Services\CashboxService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class TapPaymentFinalizerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
            'session.driver' => 'array',
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
            $table->decimal('credit', 24, 4)->default(0);
            $table->decimal('debit', 24, 4)->default(0);
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
        Schema::create('website_orders', function (Blueprint $table) {
            $table->id();
            $table->string('barcode');
            $table->unsignedInteger('country_id');
            $table->string('payment_type');
            $table->string('curr_type');
            $table->decimal('total_price', 20, 4);
            $table->decimal('paid_price', 20, 4)->default(0);
            $table->decimal('remain_price', 20, 4)->default(0);
            $table->unsignedInteger('status');
            $table->string('invoice')->nullable();
            $table->string('gateway_provider')->nullable();
            $table->string('gateway_currency')->nullable();
            $table->decimal('gateway_amount', 20, 4)->nullable();
            $table->decimal('gateway_rate', 20, 6)->nullable();
            $table->timestamp('stock_reserved_at')->nullable();
            $table->timestamp('stock_released_at')->nullable();
            $table->timestamp('payment_captured_at')->nullable();
            $table->timestamp('notifications_sent_at')->nullable();
            $table->timestamp('cashbox_posted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('barcode');
            $table->timestamps();
        });
        Schema::create('website_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('website_order_id');
            $table->unsignedBigInteger('product_color_id');
            $table->string('size')->nullable();
            $table->unsignedInteger('qty');
            $table->timestamps();
        });
        Schema::create('user_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_color_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedInteger('country_id');
            $table->string('size')->nullable();
            $table->integer('stock');
            $table->timestamps();
        });
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('label');
            $table->decimal('rate', 20, 6);
            $table->timestamps();
        });
        DB::table('currencies')->insert([
            ['name' => 'aed', 'label' => 'AED', 'rate' => 3.67, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'syp', 'label' => 'SYP', 'rate' => 13000, 'created_at' => now(), 'updated_at' => now()],
        ]);
        Schema::create('country_commerce_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('country_id');
            $table->decimal('shipping_fee_usd', 20, 4)->default(0);
            $table->decimal('free_shipping_threshold_usd', 20, 4)->nullable();
            $table->decimal('cod_fee_percent', 5, 2)->default(0);
            $table->boolean('card_enabled')->default(false);
            $table->string('gateway_currency')->default('USD');
            $table->string('gateway_mode')->default('sandbox');
            $table->unsignedBigInteger('website_cashbox_user_id')->nullable();
            $table->timestamps();
        });
        DB::table('country_commerce_settings')->insert([
            'country_id' => 2,
            'shipping_fee_usd' => 0,
            'free_shipping_threshold_usd' => null,
            'cod_fee_percent' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_duplicate_captured_events_do_not_change_reserved_stock_twice(): void
    {
        [$order, $stock] = $this->reservedOrder();
        $finalizer = $this->finalizer();
        $charge = $this->charge($order, 'CAPTURED');

        $first = $finalizer->finalize($charge);
        $second = $finalizer->finalize($charge);

        $this->assertSame(TapPaymentResult::CAPTURED, $first->state);
        $this->assertSame(TapPaymentResult::CAPTURED, $second->state);
        $this->assertSame(8, $stock->fresh()->stock);
        $this->assertSame(WebsiteOrder::STATUS_PENDING, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->payment_captured_at);
    }

    public function test_terminal_failure_releases_reserved_stock_once(): void
    {
        [$order, $stock] = $this->reservedOrder();
        $finalizer = $this->finalizer();
        $charge = $this->charge($order, 'DECLINED');

        $finalizer->finalize($charge);
        $finalizer->finalize($charge);

        $this->assertSame(10, $stock->fresh()->stock);
        $this->assertSame(WebsiteOrder::STATUS_FAILED, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->stock_released_at);
    }

    public function test_amount_or_currency_mismatch_never_marks_the_order_paid(): void
    {
        [$order, $stock] = $this->reservedOrder();
        $finalizer = $this->finalizer();
        $charge = $this->charge($order, 'CAPTURED');
        $charge['amount'] = 99;

        $result = $finalizer->finalize($charge);

        $this->assertSame(TapPaymentResult::INVALID, $result->state);
        $this->assertSame(WebsiteOrder::STATUS_UNPAID, $order->fresh()->status);
        $this->assertNull($order->fresh()->payment_captured_at);
        $this->assertSame(8, $stock->fresh()->stock);
    }

    public function test_non_terminal_tap_status_keeps_the_order_waiting(): void
    {
        [$order] = $this->reservedOrder();
        $result = $this->finalizer()
            ->finalize($this->charge($order, 'INITIATED'));

        $this->assertSame(TapPaymentResult::PENDING, $result->state);
        $this->assertSame(WebsiteOrder::STATUS_UNPAID, $order->fresh()->status);
        $this->assertNull($order->fresh()->stock_released_at);
    }

    public function test_browser_callback_uses_verified_charge_and_redirects_to_success(): void
    {
        [$order] = $this->reservedOrder();
        $tap = Mockery::mock(TapPaymentService::class);
        $tap->shouldReceive('getCharge')->once()->with('chg_test_1')->andReturn($this->charge($order, 'CAPTURED'));
        $this->app->instance(TapPaymentService::class, $tap);

        $response = $this->get('/payment/callback?tap_id=chg_test_1');

        $response->assertRedirect(route('order.success', ['id' => $order->id]));
        $this->assertNotNull($order->fresh()->payment_captured_at);
    }

    public function test_webhook_and_callback_share_the_same_idempotent_finalizer(): void
    {
        [$order, $stock] = $this->reservedOrder();
        $tap = Mockery::mock(TapPaymentService::class);
        $tap->shouldReceive('getCharge')->twice()->with('chg_test_1')->andReturn($this->charge($order, 'CAPTURED'));
        $this->app->instance(TapPaymentService::class, $tap);

        $this->postJson('/payment/webhook', ['id' => 'chg_test_1'])->assertOk()->assertJson(['status' => 'captured']);
        $this->get('/payment/callback?tap_id=chg_test_1')->assertRedirect(route('order.success', ['id' => $order->id]));

        $this->assertSame(8, $stock->fresh()->stock);
    }

    public function test_syrian_syp_invoice_posts_a_captured_tap_payment_once_to_the_usd_cashbox(): void
    {
        $cashboxId = DB::table('users')->insertGetId([
            'name' => 'Website cashbox',
            'email' => 'cashbox@example.test',
            'password' => 'x',
            'role_id' => 3,
            'country_id' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('country_commerce_settings')->insert([
            'country_id' => 4,
            'shipping_fee_usd' => 0,
            'free_shipping_threshold_usd' => null,
            'cod_fee_percent' => 0,
            'card_enabled' => true,
            'gateway_currency' => 'USD',
            'gateway_mode' => 'sandbox',
            'website_cashbox_user_id' => $cashboxId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $order = WebsiteOrder::query()->create([
            'barcode' => 'SY-CARD-1',
            'country_id' => 4,
            'payment_type' => 'card',
            'curr_type' => 'SYP',
            'total_price' => 260000,
            'paid_price' => 0,
            'remain_price' => 260000,
            'status' => WebsiteOrder::STATUS_UNPAID,
            'invoice' => 'chg_sy_1',
            'gateway_provider' => 'tap',
            'gateway_currency' => 'USD',
            'gateway_amount' => 20,
            'gateway_rate' => 13000,
            'stock_reserved_at' => now(),
            'notifications_sent_at' => now(),
        ]);
        WebsiteOrderItem::query()->create([
            'website_order_id' => $order->id,
            'product_color_id' => 55,
            'size' => 'M',
            'qty' => 1,
        ]);
        UserProduct::query()->create([
            'product_color_id' => 55,
            'user_id' => $cashboxId,
            'country_id' => 4,
            'size' => 'M',
            'stock' => 9,
        ]);
        $charge = [
            'id' => 'chg_sy_1',
            'status' => 'CAPTURED',
            'amount' => 20,
            'currency' => 'USD',
            'metadata' => ['order_id' => $order->id],
            'reference' => ['order' => $order->barcode],
        ];

        $this->finalizer()->finalize($charge);
        $this->finalizer()->finalize($charge);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $cashboxId,
            'currency_code' => 'USD',
            'credit' => 20,
        ]);
        $this->assertDatabaseCount('wallet_movements', 1);
        $this->assertNotNull($order->fresh()->cashbox_posted_at);
        $this->assertSame(260000.0, (float) $order->fresh()->paid_price);
    }

    public function test_explicit_invoice_source_prevents_same_id_collision(): void
    {
        $website = WebsiteOrder::query()->create([
            'barcode' => 'WEB-SAME-ID',
            'country_id' => 2,
            'payment_type' => 'cod',
            'curr_type' => 'AED',
            'total_price' => 10,
            'status' => WebsiteOrder::STATUS_PENDING,
        ]);
        $admin = Order::query()->create(['barcode' => 'ADMIN-SAME-ID']);
        $service = new InvoiceDataService();

        $this->assertSame($admin->id, $website->id);
        $this->assertInstanceOf(Order::class, $service->resolve('order', $admin->id));
        $this->assertInstanceOf(WebsiteOrder::class, $service->resolve('website', $website->id));
        $this->assertSame('ADMIN-SAME-ID', $service->resolve('order', $admin->id)->barcode);
        $this->assertSame('WEB-SAME-ID', $service->resolve('website', $website->id)->barcode);
    }

    public function test_payment_tracking_migration_is_idempotent(): void
    {
        Schema::drop('website_orders');
        Schema::create('website_orders', function (Blueprint $table) {
            $table->id();
            $table->string('invoice')->nullable();
            $table->timestamps();
        });

        require_once database_path('migrations/2026_09_02_000001_add_payment_tracking_to_website_orders.php');
        $migration = new \AddPaymentTrackingToWebsiteOrders();
        $migration->up();
        $migration->up();

        foreach (['stock_reserved_at', 'stock_released_at', 'payment_captured_at', 'notifications_sent_at'] as $column) {
            $this->assertTrue(Schema::hasColumn('website_orders', $column));
        }
    }

    private function reservedOrder(): array
    {
        $order = WebsiteOrder::query()->create([
            'barcode' => 'WEB-100',
            'country_id' => 2,
            'payment_type' => 'card',
            'curr_type' => 'AED',
            'total_price' => 100,
            'status' => WebsiteOrder::STATUS_UNPAID,
            'invoice' => 'chg_test_1',
            'stock_reserved_at' => now(),
            'notifications_sent_at' => now(),
        ]);
        WebsiteOrderItem::query()->create([
            'website_order_id' => $order->id,
            'product_color_id' => 50,
            'size' => 'M',
            'qty' => 2,
        ]);
        $stock = UserProduct::query()->create([
            'product_color_id' => 50,
            'country_id' => 2,
            'size' => 'M',
            'stock' => 8,
        ]);

        return [$order, $stock];
    }

    private function finalizer(): TapPaymentFinalizer
    {
        return new TapPaymentFinalizer(
            new WebsiteOrderStockService(),
            app(CashboxService::class)
        );
    }

    private function charge(WebsiteOrder $order, string $status): array
    {
        return [
            'id' => 'chg_test_1',
            'status' => $status,
            'amount' => 100,
            'currency' => 'AED',
            'metadata' => ['order_id' => $order->id],
            'reference' => ['order' => $order->barcode],
        ];
    }
}
