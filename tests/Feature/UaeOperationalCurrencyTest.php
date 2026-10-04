<?php

namespace Tests\Feature;

use App\Models\MerchantDebit;
use App\Models\User;
use App\Services\CashboxService;
use App\Services\CurrencyService;
use App\Services\SalesCurrencyPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UaeOperationalCurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('currencies')->updateOrInsert(['name' => 'aed'], ['label' => 'AED', 'rate' => 3.675]);
        app(CurrencyService::class)->clearRateCache();
    }

    private function user(int $role = User::ROLE_SHOP, int $country = User::COUNTRY_UAE): User
    {
        return User::create(['name' => 'AED operations', 'email' => uniqid('ops-') . '@example.test',
            'password' => 'x', 'role_id' => $role, 'country_id' => $country]);
    }

    public function test_expense_uses_aed_balance_and_posts_exact_local_amount(): void
    {
        $shop = $this->user();
        $cashboxes = app(CashboxService::class);
        $cashboxes->credit($shop->id, 100, 'AED', 'expense-funds');
        $this->actingAs($shop)->post(route('expenses.store'), ['amount' => 36.75, 'description' => 'Delivery'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('expenses', ['issuer_id' => $shop->id, 'amount' => 36.75, 'currency_code' => 'AED']);
        $this->assertEquals(63.25, $cashboxes->balancesForUser($shop->id)['AED']['balance']);
        $this->assertDatabaseHas('wallet_movements', ['source_type' => \App\Models\Expense::class,
            'amount' => 36.75, 'base_amount' => 10, 'currency_code' => 'AED']);
        $this->post(route('expenses.store'), ['amount' => 70, 'description' => 'Too much'])->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('expenses', 1);
    }

    public function test_merchant_payments_are_aed_without_double_conversion(): void
    {
        $shop = $this->user();
        $merchant = $this->user(User::ROLE_MERCHANT);
        // Inventory writers pass USD base cost; the new account stores transaction AED.
        $account = MerchantDebit::create(['creditor_id' => $merchant->id, 'debtor_id' => $shop->id, 'amount' => 100]);
        $this->assertEquals(367.50, $account->amount);
        $this->assertSame('AED', $account->currency_code);
        $this->actingAs($shop)->postJson(route('debits.payment'), ['debit' => $account->id, 'amount' => 67.50])
            ->assertOk()->assertJsonPath('success', true);
        $this->assertEquals(300, $account->fresh()->amount);
        $this->assertDatabaseHas('debit_payments', ['merchant_debit_id' => $account->id, 'amount' => 67.50, 'currency_code' => 'AED']);
        $this->assertSame(['AED'], DB::table('wallet_movements')->distinct()->pluck('currency_code')->all());
        $this->assertSame(0, DB::table('wallet_movements')->where('amount', '<>', 67.50)->count());
    }

    public function test_legacy_merchant_account_must_be_converted_before_new_payment(): void
    {
        $shop = $this->user();
        $merchant = $this->user(User::ROLE_MERCHANT);
        $id = DB::table('merchant_debits')->insertGetId(['creditor_id' => $merchant->id, 'debtor_id' => $shop->id, 'amount' => 100]);
        $this->actingAs($shop)->postJson(route('debits.payment'), ['debit' => $id, 'amount' => 10])
            ->assertJsonStructure(['error']);
        $this->assertDatabaseHas('merchant_debits', ['id' => $id, 'amount' => 100]);
        $this->assertDatabaseCount('debit_payments', 0);
    }

    public function test_cross_country_merchant_transactions_use_destination_shop_currency(): void
    {
        $uaeShop = $this->user();
        $foreignShop = $this->user(User::ROLE_SHOP, 1);
        $uaeMerchant = $this->user(User::ROLE_MERCHANT);
        $foreignMerchant = $this->user(User::ROLE_MERCHANT, 1);

        $toUae = MerchantDebit::create(['creditor_id' => $foreignMerchant->id,
            'debtor_id' => $uaeShop->id, 'amount' => 10]);
        $this->assertSame('AED', $toUae->currency_code);
        $this->assertEquals(36.75, $toUae->amount);

        $toForeign = MerchantDebit::create(['creditor_id' => $uaeMerchant->id,
            'debtor_id' => $foreignShop->id, 'amount' => 10]);
        $this->assertSame('USD', $toForeign->currency_code);
        $this->assertEquals(10, $toForeign->amount);
    }

    public function test_uae_website_and_admin_options_only_offer_dirhams(): void
    {
        $currencies = app(CurrencyService::class);
        $this->assertSame(['AED'], array_column($currencies->optionsForCountry(2, true), 'code'));
        $this->assertSame(['AED'], array_column($currencies->optionsForCountry(2), 'code'));
        $this->assertSame('USD', app(SalesCurrencyPolicy::class)->websiteOption(1, false)['code']);
        $this->expectException(\InvalidArgumentException::class);
        app(SalesCurrencyPolicy::class)->websiteOption(2, false, 'USD');
    }

    public function test_internal_base_cash_post_is_converted_once_and_keeps_its_idempotency_key(): void
    {
        $shop = $this->user();
        $cashboxes = app(CashboxService::class);
        $first = $cashboxes->credit($shop->id, 10, 'USD', 'base-operation');
        $again = $cashboxes->credit($shop->id, 10, 'USD', 'base-operation');
        $this->assertSame($first->id, $again->id);
        $this->assertEquals(36.75, $first->amount);
        $this->assertEquals(10, $first->base_amount);
        $this->assertSame('AED', $first->currency_code);
    }
}
