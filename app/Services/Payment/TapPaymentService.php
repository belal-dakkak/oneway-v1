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
        } else {
            // Log masked key for diagnostic purposes (first 7 and last 4)
            $masked = substr($this->secretKey, 0, 7) . '...' . substr($this->secretKey, -4);
            Log::info('Tap Payment Service initialized with key: ' . $masked);
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
        try {
            Log::info('Tap Payment Request Data: ' . json_encode($data));
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->post($this->baseUrl . '/charges', $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Tap Payment Error (Status ' . $response->status() . '): ' . $response->body());
            return $response->json(); // Return json even on error so we can parse the description
        } catch (\Exception $e) {
            Log::error('Tap Payment Exception: ' . $e->getMessage());
            return null;
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
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'accept' => 'application/json',
            ])->get($this->baseUrl . '/charges/' . $chargeId);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Tap Retrieve Charge Error: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Tap Retrieve Charge Exception: ' . $e->getMessage());
            return null;
        }
    }
}
