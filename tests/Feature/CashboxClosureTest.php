<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;
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
