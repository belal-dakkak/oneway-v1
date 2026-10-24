<?php

namespace Tests\Feature;

use App\Models\ClientDebit;
use App\Models\User;
use App\Repositories\ClientDebitRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ClientDebitRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_list_and_totals_exclude_balances_that_round_to_zero(): void
    {
        $admin = User::factory()->create([
            'role_id' => User::ROLE_ADMIN,
            'country_id' => User::COUNTRY_SYRIA,
        ]);
        $shop = User::factory()->create([
            'role_id' => User::ROLE_SHOP,
            'country_id' => User::COUNTRY_SYRIA,
        ]);
        $this->actingAs($admin);

        $zero = $this->account($shop, 'USD zero', 'USD', 0);
        $roundedUsdZero = $this->account($shop, 'USD rounded zero', 'USD', 0.004);
        $positive = $this->account($shop, 'USD debt', 'USD', 10.12);
        $negative = $this->account($shop, 'USD credit', 'USD', -5);
        $roundedSypZero = $this->account($shop, 'SYP rounded zero', 'SYP', 0.4);
        $sypDebt = $this->account($shop, 'SYP debt', 'SYP', 2);

        $repository = app(ClientDebitRepository::class);
        $clients = $repository->getClients(new Request());
        $ids = collect($clients->items())->pluck('id')->all();
        $totals = $repository->getClientsSum(new Request());

        $this->assertNotContains($zero->id, $ids);
        $this->assertNotContains($roundedUsdZero->id, $ids);
        $this->assertNotContains($roundedSypZero->id, $ids);
        $this->assertContains($positive->id, $ids);
        $this->assertContains($negative->id, $ids, 'A customer credit is not a zero account.');
        $this->assertContains($sypDebt->id, $ids);
        $this->assertEqualsWithDelta(5.12, (float) $totals['USD'], 0.00001);
        $this->assertSame(2.0, (float) $totals['SYP']);
    }

    private function account(User $shop, string $name, string $currency, float $amount): ClientDebit
    {
        $client = User::factory()->create([
            'name' => $name,
            'role_id' => User::ROLE_CLIENT,
            'country_id' => $shop->country_id,
        ]);

        return ClientDebit::query()->create([
            'creditor_id' => $shop->id,
            'debtor_id' => $client->id,
            'currency_code' => $currency,
            'amount' => $amount,
        ]);
    }
}
