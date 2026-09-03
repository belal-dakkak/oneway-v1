<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\WebsiteOrder;
use App\Services\Payment\TapPaymentFinalizer;
use App\Services\Payment\TapPaymentResult;
use App\Services\Payment\TapPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private $tapService;
    private $finalizer;

    public function __construct(TapPaymentService $tapService, TapPaymentFinalizer $finalizer)
    {
        $this->tapService = $tapService;
        $this->finalizer = $finalizer;
    }

    public function callback(Request $request)
    {
        $tapId = (string) $request->query('tap_id', '');
        if ($tapId === '') {
            return redirect()->route('homepage')->with('error', 'Payment was cancelled or did not return a Tap reference.');
        }

        $charge = $this->tapService->getCharge($tapId);
        if (!$charge) {
            $order = WebsiteOrder::query()->where('invoice', $tapId)->first();
            if ($order) {
                return redirect()->route('payment.pending', ['id' => $order->id]);
            }

            return redirect()->route('homepage')->with('error', 'Unable to verify the payment at this time.');
        }

        $result = $this->finalizer->finalize($charge);
        if ($result->state === TapPaymentResult::CAPTURED) {
            return redirect()->route('order.success', ['id' => $result->order->id]);
        }
        if ($result->state === TapPaymentResult::FAILED && $result->order) {
            return redirect()->route('payment.failed', ['id' => $result->order->id]);
        }
        if ($result->order) {
            return redirect()->route('payment.pending', ['id' => $result->order->id]);
        }

        Log::warning('Tap callback could not be associated with an order.', [
            'charge_id' => $tapId,
            'reason' => $result->message,
        ]);

        return redirect()->route('homepage')->with('error', 'The payment could not be matched to an order.');
    }

    public function webhook(Request $request)
    {
        $tapId = (string) $request->input('id', '');
        if ($tapId === '') {
            return response()->json(['status' => 'ignored'], 200);
        }

        $charge = $this->tapService->getCharge($tapId);
        if (!$charge) {
            return response()->json(['status' => 'verification_unavailable'], 503);
        }

        $result = $this->finalizer->finalize($charge);

        return response()->json([
            'status' => $result->state,
            'order_id' => $result->order ? $result->order->id : null,
        ], 200);
    }
}
