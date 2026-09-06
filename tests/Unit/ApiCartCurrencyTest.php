<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\Order\CartController;
use App\Http\Requests\Api\CheckoutRequest;
use App\Models\User;
use App\Models\WebsiteOrder;
use App\Repositories\OrderRepository;
use App\Services\SalesCurrencyPolicy;
use App\Support\Country;
use Mockery;
use Tests\TestCase;

class ApiCartCurrencyTest extends TestCase
{
    public function test_syrian_api_retail_checkout_forces_syp_and_uses_the_authenticated_user(): void
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

        $policy = Mockery::mock(SalesCurrencyPolicy::class);
        $policy->shouldReceive('websiteOption')
            ->once()->with(Country::SYRIA, false, 'USD')
            ->andReturn(['code' => 'SYP', 'rate' => 13000]);

        $repository = Mockery::mock(OrderRepository::class);
        $repository->shouldReceive('addForOnline')->once()->with(Mockery::on(function ($normalized) {
            return (int) $normalized->input('authenticated_user_id') === 44
                && (int) $normalized->input('country_id') === Country::SYRIA
                && $normalized->input('currency') === 'SYP';
        }))->andReturn(new WebsiteOrder());

        $response = (new CartController($repository, $policy))->store($request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_syrian_api_checkout_rejects_card_payment(): void
    {
        $user = new User();
        $user->forceFill(['id' => 44, 'country_id' => Country::SYRIA]);

        $request = CheckoutRequest::create('/api/checkout', 'POST', [
            'items' => [['product_id' => 1, 'qty' => 1]],
            'currency' => 'USD',
            'payment' => ['name' => 'card'],
        ]);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $policy = Mockery::mock(SalesCurrencyPolicy::class);
        $policy->shouldReceive('websiteOption')
            ->once()->andReturn(['code' => 'SYP', 'rate' => 13000]);
        $repository = Mockery::mock(OrderRepository::class);
        $repository->shouldNotReceive('addForOnline');

        $response = (new CartController($repository, $policy))->store($request);

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('error', $response->getData(true)['result']);
    }
}
