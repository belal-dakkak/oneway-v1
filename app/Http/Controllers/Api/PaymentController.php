<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class PaymentController extends ApiController
{
    public function redirectUrl(Request $request): JsonResponse
    {
        Log::query()->create([
            'url' => 'payment/tap/redirect',
            'method' => $request->getMethod(),
            'header' => $request->header(),
            'body' => $request->all(),
            'disclosure' => $request->getClientIps()
        ]);

        return $this->respondSuccess([
            'method' => $request->getMethod(),
            'data' => $request->all()
        ]);
    }

    public function webhookUrl(Request $request): JsonResponse
    {
        Log::query()->create([
            'url' => 'payment/tap/redirect',
            'method' => $request->getMethod(),
            'header' => $request->header(),
            'body' => $request->all(),
            'disclosure' => $request->getClientIps()
        ]);

        return $this->respondSuccess([
            'method' => $request->getMethod(),
            'data' => $request->all()
        ]);
    }

    public function appStatus(): JsonResponse
    {
        return $this->respondSuccess([
            'dev_status' => true,
        ]);
    }

    public function commands(): int
    {
        return Artisan::call('migrate');
    }
}
