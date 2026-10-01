<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TapPaymentService
{
    protected $secretKey;
    protected $baseUrl = 'https://api.tap.company/v2';

    public function __construct()
    {
        $this->secretKey = config('services.tap.secret_key');

        if (empty($this->secretKey)) {
            Log::error('Tap Payment Error: Secret Key is missing in configuration.');
        }
    }

    /**
     * Create a charge request to Tap.
     *
     * @param array $data
     * @return array|null
     */
    public function createCharge(array $data)
    {
        if (empty($this->secretKey)) {
            return ['errors' => [['description' => 'Payment gateway is not configured.']]];
        }

        if ($this->hasUnsafeProductionUrl($data['redirect']['url'] ?? null)
            || $this->hasUnsafeProductionUrl($data['post']['url'] ?? null)) {
            Log::error('Tap Payment Error: callback and webhook URLs must use HTTPS in production.');
            return ['errors' => [['description' => 'Payment callback URL is not configured securely.']]];
        }

        try {
            Log::info('Creating Tap charge.', [
                'order_id' => $data['metadata']['order_id'] ?? null,
                'amount' => $data['amount'] ?? null,
                'currency' => $data['currency'] ?? null,
            ]);
            $response = Http::withOptions(['connect_timeout' => 5])->timeout(15)->withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->post($this->baseUrl . '/charges', $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Tap charge request was rejected.', [
                'status' => $response->status(),
                'code' => data_get($response->json(), 'errors.0.code'),
            ]);
            return ['errors' => [[
                'code' => data_get($response->json(), 'errors.0.code'),
                'description' => 'تعذر بدء الدفع بالبطاقة. يرجى المحاولة لاحقاً أو التواصل مع المتجر. / Unable to start card payment. Please retry or contact the store.',
            ]]];
        } catch (\Exception $e) {
            Log::error('Tap payment connection failed.', ['exception' => get_class($e)]);
            return ['errors' => [['description' => 'تعذر الاتصال ببوابة الدفع. يرجى المحاولة لاحقاً. / Unable to reach the payment gateway. Please retry later.']]];
        }
    }

    /**
     * Retrieve a charge from Tap.
     *
     * @param string $chargeId
     * @return array|null
     */
    public function getCharge(string $chargeId)
    {
        if (empty($this->secretKey)) {
            return null;
        }

        try {
            $response = Http::withOptions(['connect_timeout' => 5])->timeout(15)->retry(2, 200)->withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'accept' => 'application/json',
            ])->get($this->baseUrl . '/charges/' . rawurlencode($chargeId));

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Tap charge verification request was rejected.', [
                'status' => $response->status(),
                'code' => data_get($response->json(), 'errors.0.code'),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Tap charge verification connection failed.', ['exception' => get_class($e)]);
            return null;
        }
    }

    private function hasUnsafeProductionUrl(?string $url): bool
    {
        if (!app()->environment(['production', 'live'])) {
            return false;
        }

        return !$url || stripos($url, 'https://') !== 0;
    }
}
