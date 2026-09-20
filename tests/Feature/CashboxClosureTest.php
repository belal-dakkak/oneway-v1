<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Category;
use App\Models\Color;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\Wallet;
use App\Models\WalletMovement;
use App\Services\CashboxService;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;
use Inertia\Testing\AssertableInertia as Assert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashboxClosureTest extends TestCase
{
    use RefreshDatabase;

    public function test_closing_sales_transfers_each_currency_and_returns_to_the_source_tab(): void
    {
        Currency::query()->updateOrCreate(['name' => 'syp'], ['label' => 'SYP', 'rate' => 13000]);
        $admin = $this->user(User::ROLE_ADMIN, 'closure-admin@example.test');
        $warehouse = $this->user(User::ROLE_WAREHOUSE, 'closure-warehouse@example.test');
        Wallet::query()->updateOrCreate(
            ['user_id' => $warehouse->id, 'currency_code' => 'USD'],
            ['credit' => 20, 'debit' => 5]
        );
        Wallet::query()->create(['user_id' => $warehouse->id, 'currency_code' => 'SYP', 'credit' => 260000, 'debit' => 0]);

        $this->actingAs($admin)
            ->post(route('users.wallet.close', $warehouse->id), ['return_type' => User::ROLE_WAREHOUSE])
            ->assertRedirect(route('users.index', ['type' => User::ROLE_WAREHOUSE]));

        $this->assertSame(0.0, $this->balance($warehouse, 'USD'));
        $this->assertSame(0.0, $this->balance($warehouse, 'SYP'));
        $this->assertSame(15.0, $this->balance($admin, 'USD'));
        $this->assertSame(260000.0, $this->balance($admin, 'SYP'));
        $this->assertDatabaseCount('wallet_movements', 4);
        $movements = WalletMovement::query()->where('payment_method', 'sales_closure')->get();
        $this->assertCount(1, $movements->pluck('exchange_group')->unique());
        $this->assertNotEmpty($movements->first()->exchange_group);
        $this->assertSame([$warehouse->id], $movements->pluck('source_id')->unique()->values()->all());
    }

    public function test_negative_cashbox_is_reconciled_with_an_audited_reverse_transfer(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'negative-admin@example.test');
        $shop = $this->user(User::ROLE_SHOP, 'negative-shop@example.test');
        Wallet::query()->updateOrCreate(
            ['user_id' => $shop->id, 'currency_code' => 'USD'],
            ['credit' => 0, 'debit' => 5]
        );

        $this->actingAs($admin)
            ->post(route('users.wallet.close', $shop->id), ['return_type' => User::ROLE_SHOP])
            ->assertRedirect(route('users.index', ['type' => User::ROLE_SHOP]));

        $this->assertSame(0.0, $this->balance($shop, 'USD'));
        $this->assertSame(-5.0, $this->balance($admin, 'USD'));
        $this->assertDatabaseHas('wallet_movements', [
            'user_id' => $shop->id, 'currency_code' => 'USD',
            'direction' => 'credit', 'amount' => 5,
            'payment_method' => 'sales_closure',
        ]);
        $this->assertDatabaseHas('wallet_movements', [
            'user_id' => $admin->id, 'currency_code' => 'USD',
            'direction' => 'debit', 'amount' => 5,
            'payment_method' => 'sales_closure',
        ]);
        $this->assertDatabaseCount('wallet_movements', 2);

        $this->actingAs($admin)
            ->post(route('users.wallet.close', $shop->id), ['return_type' => User::ROLE_SHOP])
            ->assertRedirect(route('users.index', ['type' => User::ROLE_SHOP]));
        $this->assertDatabaseCount('wallet_movements', 2);
    }

    public function test_branch_page_reads_the_closed_wallet_and_a_later_sale_is_a_new_balance(): void
    {
        $admin = $this->user(User::ROLE_ADMIN, 'page-admin@example.test');
        $shop = $this->user(User::ROLE_SHOP, 'page-shop@example.test');
        Wallet::query()->updateOrCreate(
            ['user_id' => $shop->id, 'currency_code' => 'USD'],
            ['credit' => 25, 'debit' => 0]
        );

        $this->actingAs($admin)->post(route('users.wallet.close', $shop->id));
        $this->actingAs($shop)->get(route('cashboxes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Cashboxes/Index')
                ->where('walletOwnerId', $shop->id)
                ->where('wallets.USD.balance', 0)
                ->etc());

        app(CashboxService::class)->credit($shop->id, 7, 'USD', 'test:new-sale');
        $this->actingAs($shop)->get(route('cashboxes.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Cashboxes/Index')
                ->where('wallets.USD.balance', 7)
                ->etc());
    }

    public function test_only_the_country_admin_can_receive_a_sales_closure(): void
    {
        $warehouse = $this->user(User::ROLE_WAREHOUSE, 'receiver-warehouse@example.test');
        $shop = $this->user(User::ROLE_SHOP, 'source-shop@example.test');
        Wallet::query()->updateOrCreate(
            ['user_id' => $shop->id, 'currency_code' => 'USD'],
            ['credit' => 25, 'debit' => 0]
        );

        $this->actingAs($warehouse)
            ->post(route('users.wallet.close', $shop->id), ['return_type' => User::ROLE_SHOP])
            ->assertForbidden();

        $this->assertSame(25.0, $this->balance($shop, 'USD'));
        $this->assertSame(0.0, $this->balance($warehouse, 'USD'));
        $this->assertDatabaseCount('wallet_movements', 0);
    }

    public function test_warehouse_sale_closure_and_refund_use_the_same_cashbox(): void
    {
        Currency::query()->updateOrCreate(['name' => 'syp'], ['label' => 'SYP', 'rate' => 13000]);
        $admin = $this->user(User::ROLE_ADMIN, 'flow-admin@example.test');
        $warehouse = $this->user(User::ROLE_WAREHOUSE, 'flow-warehouse@example.test');
        $category = Category::query()->create(['name' => 'Cashbox flow']);
        $color = Color::query()->create(['name' => 'Black', 'code' => '#000000']);
        $product = Product::query()->create([
            'name' => 'Flow product', 'barcode' => 'FLOW-P',
            'country_id' => User::COUNTRY_SYRIA, 'category_id' => $category->id, 'cost_price' => 10,
        ]);
        $variant = ProductColor::query()->create([
            'product_id' => $product->id, 'color_id' => $color->id,
            'country_id' => User::COUNTRY_SYRIA, 'barcode' => 'FLOW-C', 'sizes' => '[]', 'stock' => 0,
        ]);
        $stock = UserProduct::query()->create([
            'product_color_id' => $variant->id, 'user_id' => $warehouse->id,
            'country_id' => User::COUNTRY_SYRIA, 'size' => 'M', 'barcode' => 'FLOW-M',
            'stock' => 3, 'wholesale_price' => 10, 'retail_price' => 50,
        ]);

        $this->actingAs($warehouse);
        $firstSale = $this->createWarehouseSale($stock);
        $this->assertInstanceOf(Order::class, $firstSale);
        $sypSale = $this->createWarehouseSale($stock, 'SYP', 13000, 260000);
        $this->assertInstanceOf(Order::class, $sypSale);
        $this->assertSame(50.0, $this->balance($warehouse, 'USD'));
        $this->assertSame(260000.0, $this->balance($warehouse, 'SYP'));

        $this->actingAs($admin)->post(route('users.wallet.close', $warehouse->id), [
            'return_type' => User::ROLE_WAREHOUSE,
        ])->assertRedirect(route('users.index', ['type' => User::ROLE_WAREHOUSE]));
        $this->assertSame(0.0, $this->balance($warehouse, 'USD'));
        $this->assertSame(0.0, $this->balance($warehouse, 'SYP'));
        $this->assertSame(50.0, $this->balance($admin, 'USD'));
        $this->assertSame(260000.0, $this->balance($admin, 'SYP'));
        $this->actingAs($admin)->get(route('users.index', ['type' => User::ROLE_WAREHOUSE]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Users/Index')
                ->where('users.data.0.cashbox_balances.USD.balance', 0)
                ->where('users.data.0.cashbox_balances.SYP.balance', 0)
                ->etc());
        $this->actingAs($warehouse)->get(route('cashboxes.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Cashboxes/Index')
                ->where('walletOwnerId', $warehouse->id)
                ->where('wallets.USD.balance', 0)
                ->where('wallets.SYP.balance', 0)
                ->etc());

        $secondSale = $this->createWarehouseSale($stock);
        $this->assertInstanceOf(Order::class, $secondSale);
        $this->assertSame(50.0, $this->balance($warehouse, 'USD'));
        $item = $secondSale->items()->first();
        $this->post(route('refunds.match'), ['product' => $stock->barcode])
            ->assertOk()
            ->assertJsonPath('price', 50)
            ->assertJsonPath('max_refund_unit_price', 50);
        $this->post(route('refunds.store'), [
            'selected_products' => [[
                'product_id' => $item->id, 'qty' => 1, 'price' => '20.001',
            ]],
        ])->assertSessionHasErrors('selected_products.0.price');
        $this->assertSame(50.0, $this->balance($warehouse, 'USD'));
        $this->post(route('refunds.store'), [
            'selected_products' => [[
                'product_id' => $item->id, 'qty' => 1, 'price' => '20.00',
            ]],
        ])->assertRedirect(route('refunds.index'));
        $refund = $item->refunds()->first();
        $this->assertSame(20.0, (float) $refund->total_price_paid);
        $this->assertSame(30.0, $this->balance($warehouse, 'USD'));
        $this->assertSame(50.0, $this->balance($admin, 'USD'));
        $this->assertSame(260000.0, $this->balance($admin, 'SYP'));
        $this->actingAs($warehouse)->get(route('cashboxes.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('wallets.USD.balance', 30)
                ->etc());
        $this->assertSame(3, WalletMovement::query()
            ->where('user_id', $warehouse->id)->where('direction', 'credit')->count());
        $this->assertSame(3, WalletMovement::query()
            ->where('user_id', $warehouse->id)->where('direction', 'debit')->count());
        $this->assertSame(1, WalletMovement::query()->where('user_id', $admin->id)
            ->where('currency_code', 'SYP')->where('payment_method', 'sales_closure')->count());
    }

    private function createWarehouseSale(UserProduct $stock, string $currency = 'USD', float $rate = 1, float $price = 50)
    {
        return app(OrderRepository::class)->add(Request::create('/admin/orders', 'POST', [
            'type' => Order::TYPE_CASH,
            'order_type' => 'simple',
            'total_price_before_discount' => $price,
            'currency' => ['value' => $currency, 'code' => $currency, 'rate' => $rate],
            'selected_products' => [[
                'product_id' => $stock->id, 'qty' => 1, 'price' => $price,
            ]],
        ]));
    }

    private function user(int $role, string $email): User
    {
        return User::query()->create([
            'name' => $email, 'email' => $email, 'password' => 'secret',
            'role_id' => $role, 'country_id' => User::COUNTRY_SYRIA,
        ]);
    }

    private function balance(User $user, string $currency): float
    {
        $wallet = Wallet::query()->where('user_id', $user->id)->where('currency_code', $currency)->first();
        return $wallet ? (float) $wallet->credit - (float) $wallet->debit : 0.0;
    }
}
