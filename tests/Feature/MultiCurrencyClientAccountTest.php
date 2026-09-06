<?php

namespace Tests\Feature;

use App\Models\ClientDebit;
use App\Models\Order;
use App\Models\User;
use App\Services\ClientAccountService;
use App\Services\CurrencyService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MultiCurrencyClientAccountTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => false,
            'cache.default' => 'array',
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
            $table->decimal('credit', 20, 4)->default(0);
            $table->decimal('debit', 20, 4)->default(0);
            $table->timestamps();
        });
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('label');
            $table->decimal('rate', 20, 6);
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('buyer_id')->nullable();
            $table->string('barcode');
            $table->unsignedInteger('type')->default(Order::TYPE_FOR_CLIENT);
            $table->string('order_type')->nullable();
            $table->string('curr_type', 8)->default('USD');
            $table->decimal('curr_rate', 20, 6)->default(1);
            $table->decimal('total_price', 20, 4)->default(0);
            $table->decimal('paid_price', 20, 4)->default(0);
            $table->decimal('remain_price', 20, 4)->default(0);
            $table->timestamps();
        });
        Schema::create('client_debits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('creditor_id');
            $table->unsignedBigInteger('debtor_id');
            $table->decimal('amount', 20, 4)->default(0);
            $table->string('currency_code', 3)->default('USD');
            $table->timestamps();
        });
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->decimal('pay_amount', 20, 4);
            $table->decimal('exchange_rate', 20, 6)->default(1);
            $table->decimal('base_amount', 20, 4)->default(0);
            $table->timestamps();
        });
        Schema::create('client_debit_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_debit_id');
            $table->decimal('amount', 20, 4);
            $table->decimal('exchange_rate', 20, 6)->default(1);
            $table->decimal('base_amount', 20, 4)->default(0);
            $table->timestamps();
        });
        Schema::create('client_debit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_debit_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('client_debit_payment_id')->nullable();
            $table->unsignedBigInteger('client_refund_id')->nullable();
            $table->text('note');
            $table->decimal('amount', 20, 4);
            $table->string('currency_code', 3);
            $table->decimal('exchange_rate', 20, 6)->default(1);
            $table->decimal('base_amount', 20, 4)->default(0);
            $table->timestamps();
        });

        DB::table('currencies')->insert([
            'name' => 'syp', 'label' => 'SYP', 'rate' => 13000,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        app(CurrencyService::class)->clearRateCache();
    }

    public function test_customer_has_separate_syp_and_usd_accounts_and_syp_payments_enter_wallet_in_usd(): void
    {
        $seller = User::query()->create([
            'name' => 'Syrian shop', 'email' => 'shop@example.test', 'password' => 'x',
            'role_id' => User::ROLE_SHOP, 'country_id' => User::COUNTRY_SYRIA,
        ]);
        $buyer = User::query()->create([
            'name' => 'Customer', 'email' => 'buyer@example.test', 'password' => 'x',
            'role_id' => User::ROLE_CLIENT, 'country_id' => User::COUNTRY_SYRIA,
        ]);
        $seller->wallet()->update(['credit' => 0, 'debit' => 0]);

        $sypOrder = Order::query()->create([
            'seller_id' => $seller->id, 'buyer_id' => $buyer->id, 'barcode' => 'SYP-1',
            'curr_type' => 'SYP', 'curr_rate' => 13000, 'total_price' => 260000,
            'paid_price' => 0, 'remain_price' => 260000,
        ]);
        $usdOrder = Order::query()->create([
            'seller_id' => $seller->id, 'buyer_id' => $buyer->id, 'barcode' => 'USD-1',
            'curr_type' => 'USD', 'curr_rate' => 1, 'total_price' => 20,
            'paid_price' => 0, 'remain_price' => 20,
        ]);

        $service = app(ClientAccountService::class);
        DB::transaction(function () use ($service, $sypOrder, $usdOrder) {
            $service->syncOrderDebt($sypOrder);
            $service->syncOrderDebt($usdOrder);
        });

        $this->assertSame(2, ClientDebit::query()->count());
        $sypAccount = ClientDebit::query()->where('currency_code', 'SYP')->firstOrFail();
        $usdAccount = ClientDebit::query()->where('currency_code', 'USD')->firstOrFail();
        $this->assertSame(260000.0, (float) $sypAccount->amount);
        $this->assertSame(20.0, (float) $usdAccount->amount);

        $service->payAccount($sypAccount, 130000);

        $this->assertSame(130000.0, (float) $sypAccount->fresh()->amount);
        $this->assertSame(20.0, (float) $usdAccount->fresh()->amount);
        $this->assertSame(130000.0, (float) $sypOrder->fresh()->remain_price);
        $this->assertSame(20.0, (float) $usdOrder->fresh()->remain_price);
        $this->assertSame(10.0, (float) $seller->wallet->fresh()->credit);
        $this->assertDatabaseHas('client_debit_payments', [
            'client_debit_id' => $sypAccount->id,
            'amount' => 130000,
            'exchange_rate' => 13000,
            'base_amount' => 10,
        ]);

        DB::table('currencies')->where('name', 'syp')->update(['rate' => 10000]);
        app(CurrencyService::class)->clearRateCache();
        $service->payAccount($sypAccount->fresh(), 130000);

        $this->assertSame(23.0, (float) $seller->wallet->fresh()->credit);
        $this->assertSame(0.0, (float) $sypOrder->fresh()->remain_price);
        $this->assertSame(20.0, (float) $usdOrder->fresh()->remain_price);
    }
}
