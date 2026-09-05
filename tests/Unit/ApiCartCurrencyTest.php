<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\Order\CartController;
use App\Http\Requests\Api\CheckoutRequest;
use App\Models\User;
use App\Models\WebsiteOrder;
use App\Repositories\OrderRepository;
use App\Services\CurrencyService;
use App\Support\Country;
use Mockery;
use Tests\TestCase;

class ApiCartCurrencyTest extends TestCase
{
    public function test_api_checkout_uses_the_sanctum_user_and_ignores_a_supplied_user_id(): void
    {
        $user = new User();
        $user->forceFill(['id' => 44, 'country_id' => Country::SYRIA]);

        $request = CheckoutRequest::create('/api/checkout', 'POST', [
            'user_id' => 99,
            'items' => [['product_id' => 1, 'qty' => 1]],
            'currency' => 'USD',
            'payment' => ['name' => 'cod'],
        ]);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $currency = Mockery::mock(CurrencyService::class);
        $currency->shouldReceive('validateForCountry')
            ->once()->with('USD', Country::SYRIA, true)->andReturn('USD');

        $repository = Mockery::mock(OrderRepository::class);
        $repository->shouldReceive('addForOnline')->once()->with(Mockery::on(function ($normalized) {
            return (int) $normalized->input('authenticated_user_id') === 44
                && (int) $normalized->input('country_id') === Country::SYRIA
                && $normalized->input('currency') === 'USD';
        }))->andReturn(new WebsiteOrder());

        $response = (new CartController($repository, $currency))->store($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_api_checkout_rejects_syp_as_a_syrian_transaction_currency(): void
    {
        $user = new User();
        $user->forceFill(['id' => 44, 'country_id' => Country::SYRIA]);

        $request = CheckoutRequest::create('/api/checkout', 'POST', [
            'items' => [['product_id' => 1, 'qty' => 1]],
            'currency' => 'SYP',
            'payment' => ['name' => 'cod'],
        ]);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $repository = Mockery::mock(OrderRepository::class);
        $repository->shouldNotReceive('addForOnline');

        $response = (new CartController($repository, new CurrencyService()))->store($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('error', $response->getData(true)['result']);
    }
}
