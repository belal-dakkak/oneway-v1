<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WalletMovement;
use App\Services\CashboxService;
use App\Services\CurrencyService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class CashboxServiceTest extends TestCase
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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('label');
            $table->decimal('rate', 20, 6);
            $table->timestamps();
        });
        DB::table('currencies')->insert([
            'name' => 'syp', 'label' => 'SYP', 'rate' => 13000,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        app(CurrencyService::class)->clearRateCache();
    }

    public function test_usd_and_syp_are_independent_and_posting_is_idempotent(): void
    {
        $user = $this->user();
        $cashboxes = app(CashboxService::class);

        $cashboxes->credit($user->id, 20, 'USD', 'sale-usd-1');
        $cashboxes->credit($user->id, 260000, 'SYP', 'sale-syp-1', ['exchange_rate' => 13000]);
        $cashboxes->credit($user->id, 260000, 'SYP', 'sale-syp-1', ['exchange_rate' => 13000]);

        $this->assertSame(20.0, $this->balance($user->id, 'USD'));
        $this->assertSame(260000.0, $this->balance($user->id, 'SYP'));
        $this->assertSame(2, WalletMovement::query()->count());
        $this->assertDatabaseHas('wallet_movements', [
            'idempotency_key' => 'sale-syp-1',
            'amount' => 260000,
            'base_amount' => 20,
        ]);
    }

    public function test_manual_exchange_moves_value_atomically_between_cashboxes(): void
    {
        $user = $this->user();
        $cashboxes = app(CashboxService::class);
        $cashboxes->credit($user->id, 20, 'USD', 'opening-usd');

        $cashboxes->exchange($user->id, 'USD', 'SYP', 10, 13000, 'Daily exchange');

        $this->assertSame(10.0, $this->balance($user->id, 'USD'));
        $this->assertSame(130000.0, $this->balance($user->id, 'SYP'));
        $this->assertSame(3, WalletMovement::query()->count());
        $this->assertSame(1, WalletMovement::query()->whereNotNull('exchange_group')->distinct()->count('exchange_group'));
    }

    public function test_failed_exchange_does_not_create_partial_movements(): void
    {
        $user = $this->user();

        try {
            app(CashboxService::class)->exchange($user->id, 'USD', 'SYP', 10, 13000);
            $this->fail('Expected an insufficient-balance exception.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Insufficient cashbox balance.', $exception->getMessage());
        }

        $this->assertSame(0.0, $this->balance($user->id, 'USD'));
        $this->assertSame(0, WalletMovement::query()->count());
    }

    public function test_reconciliation_migration_merges_legacy_duplicate_wallets_and_keeps_movements(): void
    {
        $user = $this->user();
        $canonicalId = (int) DB::table('wallets')->where('user_id', $user->id)->value('id');
        DB::table('wallets')->where('id', $canonicalId)->update(['credit' => 10, 'debit' => 2]);
        $duplicateId = DB::table('wallets')->insertGetId([
            'user_id' => $user->id,
            'currency_code' => 'USD',
            'credit' => 7,
            'debit' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('wallet_movements')->insert([
            'wallet_id' => $duplicateId,
            'user_id' => $user->id,
            'currency_code' => 'USD',
            'direction' => 'credit',
            'amount' => 7,
            'exchange_rate' => 1,
            'base_amount' => 7,
            'balance_after' => 6,
            'idempotency_key' => 'legacy-duplicate-wallet',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        require_once database_path('migrations/2026_09_12_000001_reconcile_inventory_reporting_and_notifications.php');
        (new \ReconcileInventoryReportingAndNotifications())->up();

        $this->assertSame(1, DB::table('wallets')->where('user_id', $user->id)->where('currency_code', 'USD')->count());
        $this->assertDatabaseHas('wallets', ['id' => $canonicalId, 'credit' => 17, 'debit' => 3]);
        $this->assertDatabaseHas('wallet_movements', [
            'idempotency_key' => 'legacy-duplicate-wallet',
            'wallet_id' => $canonicalId,
        ]);
    }

    private function user(): User
    {
        return User::query()->create([
            'name' => 'Syrian shop',
            'email' => uniqid('cashbox-', true) . '@example.test',
            'password' => 'x',
            'role_id' => User::ROLE_SHOP,
            'country_id' => User::COUNTRY_SYRIA,
        ]);
    }

    private function balance(int $userId, string $currency): float
    {
        $wallet = DB::table('wallets')
            ->where('user_id', $userId)
            ->where('currency_code', $currency)
            ->first();

        return $wallet ? (float) $wallet->credit - (float) $wallet->debit : 0.0;
    }
}
