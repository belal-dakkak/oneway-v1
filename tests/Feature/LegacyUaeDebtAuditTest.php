<?php

namespace Tests\Feature;

use App\Models\ClientDebit;
use App\Models\Order;
use App\Models\User;
use App\Services\ClientAccountService;
use App\Services\LegacyUaeDebtAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyUaeDebtAuditTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $seller = User::query()->create(['name' => 'Shop', 'email' => 'audit-shop@example.test', 'password' => 'x', 'role_id' => User::ROLE_SHOP, 'country_id' => 2]);
        $buyer = User::query()->create(['name' => 'Buyer', 'email' => 'audit-buyer@example.test', 'password' => 'x', 'role_id' => User::ROLE_CLIENT, 'country_id' => 2]);
        $order = Order::query()->create(['seller_id' => $seller->id, 'buyer_id' => $buyer->id, 'barcode' => 'AUDIT-1',
            'curr_type' => 'AED', 'curr_rate' => 3.67, 'total_price' => 120, 'paid_price' => 20, 'remain_price' => 100]);
        $source = ClientDebit::query()->create(['creditor_id' => $seller->id, 'debtor_id' => $buyer->id, 'amount' => 100, 'currency_code' => 'USD']);
        DB::table('client_debits')->where('id', $source->id)->update(['created_at' => '2026-08-01 00:00:00']);
        $payment = DB::table('client_debit_payments')->insertGetId(['client_debit_id' => $source->id, 'amount' => 20, 'exchange_rate' => 1, 'base_amount' => 0]);
        DB::table('order_payments')->insert(['order_id' => $order->id, 'pay_amount' => 20, 'exchange_rate' => 1, 'base_amount' => 0]);
        foreach ([['order_id' => $order->id, 'client_debit_payment_id' => null, 'amount' => 120],
            ['order_id' => null, 'client_debit_payment_id' => $payment, 'amount' => -20]] as $row) {
            DB::table('client_debit_logs')->insert($row + ['client_debit_id' => $source->id, 'note' => 'Historical entry',
                'currency_code' => 'USD', 'exchange_rate' => 1, 'base_amount' => 0]);
        }
        $newOrder = Order::query()->create(['seller_id' => $seller->id, 'buyer_id' => $buyer->id, 'barcode' => 'AUDIT-2',
            'curr_type' => 'AED', 'curr_rate' => 3.67, 'total_price' => 50, 'paid_price' => 0, 'remain_price' => 50]);
        app(ClientAccountService::class)->syncOrderDebt($newOrder);
        return [$source->fresh(), $order, $payment];
    }

    public function test_report_is_read_only_and_reviewed_repair_preserves_payments_and_is_idempotent(): void
    {
        [$source, $order, $payment] = $this->fixture();
        $audit = app(LegacyUaeDebtAudit::class);
        $report = $audit->report();
        $this->assertSame('proven_aed_label', $report[0]['status']);
        $this->assertSame(100.0, $source->fresh()->amount);
        $before = $order->fresh()->getAttributes();
        $this->assertSame('applied', $audit->apply($report[0]));
        $this->assertSame('already_applied', $audit->apply($report[0]));
        $this->assertSame(0.0, $source->fresh()->amount);
        $target = ClientDebit::where('currency_code', 'AED')->firstOrFail();
        $this->assertSame(150.0, $target->amount);
        $this->assertSame($target->id, (int) DB::table('client_debit_payments')->where('id', $payment)->value('client_debit_id'));
        $this->assertEquals(20, DB::table('client_debit_payments')->where('id', $payment)->value('amount'));
        $this->assertSame($before, $order->fresh()->getAttributes());
        $this->assertSame(0, DB::table('wallet_movements')->count());
    }

    public function test_mixed_currencies_and_balance_conflicts_are_not_relabelled(): void
    {
        [$source, $order] = $this->fixture();
        $order->update(['curr_type' => 'USD']);
        $audit = app(LegacyUaeDebtAudit::class);
        $report = $audit->inspect($source);
        $this->assertSame('review_required', $report['status']);
        $this->assertContains('missing_or_mixed_currency_orders', $report['reasons']);
        $this->expectException(\RuntimeException::class);
        $audit->apply($report);
    }

    public function test_changed_data_invalidates_a_previously_reviewed_report(): void
    {
        [$source] = $this->fixture();
        $audit = app(LegacyUaeDebtAudit::class);
        $report = $audit->inspect($source);
        $source->update(['amount' => 90]);
        $this->expectException(\RuntimeException::class);
        $audit->apply($report);
    }
}
