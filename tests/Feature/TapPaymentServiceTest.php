<?php

namespace Tests\Feature;

use App\Services\Payment\TapPaymentService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TapPaymentServiceTest extends TestCase
{
    public function test_missing_configuration_never_sends_a_charge(): void
    {
        config(['services.tap.secret_key' => '']);
        Http::fake();
        $result = app(TapPaymentService::class)->createCharge([]);
        $this->assertNotEmpty($result['errors'][0]['description']);
        Http::assertNothingSent();
    }

    public function test_gateway_rejection_and_network_failure_return_safe_customer_errors(): void
    {
        config(['services.tap.secret_key' => 'sk_test_fake']);
        Http::fake(['api.tap.company/*' => Http::response(['errors' => [['code' => '123', 'description' => 'private internal detail']]], 400)]);
        $result = app(TapPaymentService::class)->createCharge(['amount' => 55, 'currency' => 'AED']);
        $this->assertSame('123', $result['errors'][0]['code']);
        $this->assertStringNotContainsString('private internal detail', $result['errors'][0]['description']);
        Http::fake(fn () => throw new ConnectionException('connection failed'));
        $result = app(TapPaymentService::class)->createCharge(['amount' => 55, 'currency' => 'AED']);
        $this->assertStringContainsString('Unable to reach', $result['errors'][0]['description']);
    }

    public function test_successful_charge_retains_gateway_redirect_and_reference(): void
    {
        config(['services.tap.secret_key' => 'sk_test_fake']);
        Http::fake(['api.tap.company/*' => Http::response(['id' => 'chg_test', 'transaction' => ['url' => 'https://example.test/pay']], 200)]);
        $result = app(TapPaymentService::class)->createCharge(['amount' => 55, 'currency' => 'AED']);
        $this->assertSame('chg_test', $result['id']);
        $this->assertSame('https://example.test/pay', $result['transaction']['url']);
        Http::assertSentCount(1);
    }

    public function test_charge_verification_uses_supported_http_client_options(): void
    {
        config(['services.tap.secret_key' => 'sk_test_fake']);
        Http::fake(['api.tap.company/v2/charges/chg_test' => Http::response(['id' => 'chg_test', 'status' => 'CAPTURED'], 200)]);
        $result = app(TapPaymentService::class)->getCharge('chg_test');
        $this->assertSame('CAPTURED', $result['status']);
        Http::assertSentCount(1);
    }
}
