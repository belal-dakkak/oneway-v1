<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\WebsiteOrder;
use App\Services\Payment\TapPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $tapService;

    public function __construct(TapPaymentService $tapService)
    {
        $this->tapService = $tapService;
    }

    public function callback(Request $request)
    {
        $tap_id = $request->get('tap_id');

        if (!$tap_id) {
            return redirect()->route('homepage')->with('error', 'Payment failed or was cancelled.');
        }

        $charge = $this->tapService->getCharge($tap_id);

        if (!$charge) {
            return redirect()->route('homepage')->with('error', 'Unable to verify payment.');
        }

        $orderId = $charge['metadata']['order_id'] ?? null;
        $order = WebsiteOrder::find($orderId);

        if (!$order) {
            Log::error("Order not found for Tap charge: " . $tap_id);
            return redirect()->route('homepage')->with('error', 'Order not found.');
        }

        if ($charge['status'] === 'CAPTURED') {
            $shouldNotify = ($order->status == WebsiteOrder::STATUS_UNPAID || $order->status == WebsiteOrder::STATUS_FAILED);

            $order->update([
                'status' => WebsiteOrder::STATUS_PENDING,
                'invoice' => $tap_id,
            ]);

            if ($shouldNotify) {
                $order->dispatchNotifications();
            }

            return redirect()->route('order.success', ['id' => $order->id]);
        }

        // Handle other statuses (CANCELLED, FAILED)
        Log::warning("Tap payment failed for order {$order->id}. Status: " . $charge['status']);

        $order->update([
            'status' => WebsiteOrder::STATUS_FAILED,
        ]);
        
        return redirect()->route('payment.failed', ['id' => $order->id]);
    }

    public function webhook(Request $request)
    {
        // For production, you should verify the signature or IP if Tap provides it
        $data = $request->all();
        $tap_id = $data['id'] ?? null;

        if (!$tap_id) return response()->json(['status' => 'ignored'], 200);

        $charge = $this->tapService->getCharge($tap_id);
        
        if ($charge) {
            $orderId = $charge['metadata']['order_id'] ?? null;
            $order = WebsiteOrder::find($orderId);

            if ($order) {
                if ($charge['status'] === 'CAPTURED') {
                    $shouldNotify = ($order->status == WebsiteOrder::STATUS_UNPAID || $order->status == WebsiteOrder::STATUS_FAILED);

                    $order->update([
                        'status' => WebsiteOrder::STATUS_PENDING,
                        'invoice' => $tap_id,
                    ]);

                    if ($shouldNotify) {
                        $order->dispatchNotifications();
                    }
                } else {
                    if ($order->status == WebsiteOrder::STATUS_UNPAID) {
                        $order->update([
                            'status' => WebsiteOrder::STATUS_FAILED,
                        ]);
                    }
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
