<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\WebsiteOrder;
use App\Models\Setting;
use App\Models\User;
use App\Models\CountryCommerceSetting;
use App\Rules\ValidPhone;
use App\Repositories\OrderRepository;
use App\Services\Payment\TapPaymentService;
use App\Services\Payment\WebsiteOrderStockService;
use App\Jobs\NotificationOrderJob;
use App\Mail\NewOrderAdminEmail;
use App\Mail\OrderConfirmationEmail;
use App\Services\CurrencyService;
use App\Services\SalesCurrencyPolicy;
use App\Services\WebsitePricingService;
use App\Support\Country;
use App\Support\SyriaGovernorates;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    private $orderRepository;
    private $tapService;
    private $currencyService;
    private $stockService;
    private $salesCurrencyPolicy;
    private $websitePricing;

    public function __construct(
        OrderRepository $orderRepository,
        TapPaymentService $tapService,
        CurrencyService $currencyService,
        WebsiteOrderStockService $stockService,
        SalesCurrencyPolicy $salesCurrencyPolicy,
        WebsitePricingService $websitePricing
    ) {
        $this->orderRepository = $orderRepository;
        $this->tapService = $tapService;
        $this->currencyService = $currencyService;
        $this->stockService = $stockService;
        $this->salesCurrencyPolicy = $salesCurrencyPolicy;
        $this->websitePricing = $websitePricing;
    }

    public function cart(): Response
    {
        $categories = Category::limit(6)->get();
        $categories = transformDataForVue($categories);

        $country = Country::id();
        $language = 'en';
        $settings = Setting::where('country', $country)->where('language', $language)->pluck('value', Setting::keyColumn())->toArray();

        return Inertia::render('Cart', [
            'categories' => $categories,
            'phone' => $settings['phone'] ?? '',
            'email' => $settings['email'] ?? '',
            'facebook' => $settings['facebook'] ?? '',
            'instagram' => $settings['instagram'] ?? '',
            'tiktok' => $settings['tiktok'] ?? '',
            'address' => $settings['address'] ?? '',
        ]);
    }

    public function checkout(): Response
    {
        $categories = Category::limit(6)->get();
        $categories = transformDataForVue($categories);

        $country = Country::id();
        $language = 'en';
        $settings = Setting::where('country', $country)->where('language', $language)->pluck('value', Setting::keyColumn())->toArray();

        return Inertia::render('Checkout', [
            'categories' => $categories,
            'phone' => $settings['phone'] ?? '',
            'email' => $settings['email'] ?? '',
            'facebook' => $settings['facebook'] ?? '',
            'instagram' => $settings['instagram'] ?? '',
            'tiktok' => $settings['tiktok'] ?? '',
            'address' => $settings['address'] ?? '',
            'syriaGovernorates' => SyriaGovernorates::all(),
        ]);
    }

    public function quote(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.color.id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.size' => 'required|string',
            'payment_method' => 'required|in:cod,card',
        ]);

        try {
            return response()->json($this->websitePricing->quote(
                (array) $request->input('items'),
                Country::id(),
                (bool) Session::get('is_merchant'),
                (string) $request->input('payment_method', 'cod')
            ));
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function placeOrder(Request $request)
    {

        $rules = [
            'items' => 'required|array|min:1',
            'items.*.color.id' => 'required|integer|exists:product_colors,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.size' => 'required',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone' => ['required', 'string', new ValidPhone(Country::code())],
            'email' => 'required|email',
            'address' => 'required|string',
            'city' => 'required|string',
            'building_name' => 'required|string',
            'flat_number' => 'required|string',
            'payment_method' => 'required|in:cod,card',
            'currency' => 'nullable|string',
        ];
        if (Country::id() === User::COUNTRY_SYRIA) {
            $rules['city'] = ['required', 'string', \Illuminate\Validation\Rule::in(SyriaGovernorates::all())];
        }
        $request->validate($rules);

        $countryId = Country::id();
        try {
            $currencyCode = $this->salesCurrencyPolicy
                ->websiteOption($countryId, (bool) Session::get('is_merchant'), $request->currency)
                ['code'];
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['currency' => 'العملة المختارة غير متاحة لهذا البلد.']);
        }
        $commerce = CountryCommerceSetting::forCountry($countryId);
        if ($request->payment_method === 'card' && !$commerce->cardIsAvailable()) {
            return back()->withErrors(['payment_method' => 'الدفع الإلكتروني غير متاح حالياً لهذا البلد.']);
        }

        if (Session::get('is_merchant')) {
            foreach ($request->items as $item) {
                if ($item['quantity'] < 12) {
                    return back()->withErrors(['items' => 'For merchant accounts, each item must have a minimum quantity of 12.']);
                }
            }
            $totalQuantity = collect($request->items)->sum('quantity');
            if ($totalQuantity < 20) {
                return back()->withErrors(['items' => 'For merchant accounts, a minimum total quantity of 20 items is required.']);
            }
        }

        // Map web request to repository format
        $items = [];
        foreach ($request->items as $item) {
            $items[] = [
                'product_id' => $item['color']['id'], // Based on addForOnline repo logic, it might expect product_color_id
                'qty' => $item['quantity'],
                'size' => $item['size'],
            ];
        }

        $orderRequestData = [
            'order_type' => 'website',
            'notes' => "Name: {$request->first_name} {$request->last_name}, Email: {$request->email}, Phone: {$request->phone}, Address: {$request->address}, City: {$request->city}, Building: {$request->building_name}, Flat: {$request->flat_number}",
            'items' => $items,
            'payment' => ['name' => $request->payment_method],
            'currency' => $currencyCode,
            'pricing_mode' => Session::get('is_merchant') ? 'wholesale' : 'retail',
            'shipping_details_id' => null, // We could store address separately if needed
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'building_name' => $request->building_name,
            'flat_number' => $request->flat_number,
        ];

        // A guest order keeps its contact data on website_orders. Checkout must
        // not create an inaccessible account or replace the current session.
        $user = auth()->user();
        if ($user && (int) $user->country_id !== (int) $countryId) {
            return back()->withErrors([
                'email' => 'هذا البريد الإلكتروني مرتبط بحساب في فرع آخر.',
            ]);
        }
        try {
            $order = $this->orderRepository->addForOnline(new Request($orderRequestData));
        } catch (Throwable $exception) {
            Log::warning('Website checkout failed.', [
                'country_id' => $countryId,
                'message' => $exception->getMessage(),
            ]);
            return back()->withErrors(['order' => $exception->getMessage()]);
        }

        if ($request->payment_method === 'card') {
            // Clean phone number: strip country codes and leading zeros for Tap
            $cleanPhone = $request->phone;
            $cleanPhone = preg_replace('/^(\+971|00971|971|\+961|00961|961|\+963|00963|963)/', '', $cleanPhone);
            $cleanPhone = ltrim($cleanPhone, '0');
            $cleanPhone = preg_replace('/\D/', '', $cleanPhone); // Remove any remaining non-digits

            $amount = round((float) ($order->gateway_amount ?: $order->total_price), 2);
            $currency = strtoupper((string) ($order->gateway_currency ?: $order->curr_type));

            if ($amount <= 0) {
                $this->stockService->release($order);
                $order->update(['status' => WebsiteOrder::STATUS_FAILED]);
                Log::error("Tap Payment Error: Invalid order amount ($amount $currency) for order #{$order->id}");
                return back()->withErrors(['error' => "Invalid order amount. Please try again or contact support."]);
            }

            $chargeData = [
                'amount' => $amount,
                'currency' => $currency,
                'customer' => [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'phone' => [
                        'country_code' => Country::definitionFromId($order->country_id)['phone_code'],
                        'number' => $cleanPhone,
                    ],
                ],
                'source' => ['id' => 'src_card'],
                'threeDSecure' => true,
                'description' => "Order #{$order->barcode}",
                'reference' => [
                    'transaction' => "txn_{$order->id}_" . time(),
                    'order' => $order->barcode,
                ],
                'receipt' => [
                    'email' => true,
                    'sms' => true,
                ],
                'redirect' => ['url' => $this->tapEndpointUrl('callback_url', 'payment/callback')],
                'post' => ['url' => $this->tapEndpointUrl('webhook_url', 'payment/webhook')],
                'metadata' => [
                    'order_id' => $order->id,
                ],
            ];

            $charge = $this->tapService->createCharge($chargeData);

            if ($charge && isset($charge['transaction']['url'])) {
                // Update order with tap_id (invoice)
                $order->update(['invoice' => $charge['id']]);

                // We do NOT dispatch notifications here for card payment.
                // They will be dispatched when the payment is completed (CAPTURED) in callback/webhook.

                return Inertia::location($charge['transaction']['url']);
            }

            // Keep the failed order for reconciliation and release its reservation once.
            $this->stockService->release($order);
            $order->update(['status' => WebsiteOrder::STATUS_FAILED]);
            $errorMessage = $charge['errors'][0]['description'] ?? 'Payment gateway error. Please try again.';
            return back()->withErrors(['payment' => $errorMessage]);
        }

        // Queue notification work so SMTP latency never blocks checkout.
        if (!$order->notifications_sent_at) {
            $order->dispatchNotifications();
        }
        return redirect()->route('order.success', ['id' => $order->id]);
    }

    public function success($id): Response
    {
        $order = WebsiteOrder::findOrFail($id);
        $categories = Category::limit(6)->get();
        $categories = transformDataForVue($categories);

        $country = $order->country_id;
        $language = 'en';
        $settings = Setting::where('country', $country)->where('language', $language)->pluck('value', Setting::keyColumn())->toArray();

        return Inertia::render('OrderSuccess', [
            'order' => $order,
            'categories' => $categories,
            'phone' => $settings['phone'] ?? '',
            'email' => $settings['email'] ?? '',
            'facebook' => $settings['facebook'] ?? '',
            'instagram' => $settings['instagram'] ?? '',
            'tiktok' => $settings['tiktok'] ?? '',
            'address' => $settings['address'] ?? '',
        ]);
    }

    public function paymentFailed($id): Response
    {
        $order = WebsiteOrder::findOrFail($id);
        $categories = Category::limit(6)->get();
        $categories = transformDataForVue($categories);

        $country = $order->country_id;
        $language = 'en';
        $settings = Setting::where('country', $country)->where('language', $language)->pluck('value', Setting::keyColumn())->toArray();

        return Inertia::render('PaymentFailed', [
            'order' => $order,
            'categories' => $categories,
            'phone' => $settings['phone'] ?? '',
            'email' => $settings['email'] ?? '',
            'facebook' => $settings['facebook'] ?? '',
            'instagram' => $settings['instagram'] ?? '',
            'tiktok' => $settings['tiktok'] ?? '',
            'address' => $settings['address'] ?? '',
        ]);
    }

    public function paymentPending($id): Response
    {
        $order = WebsiteOrder::findOrFail($id);
        $categories = transformDataForVue(Category::limit(6)->get());
        $settings = Setting::where('country', $order->country_id)
            ->where('language', 'en')
            ->pluck('value', Setting::keyColumn())
            ->toArray();

        return Inertia::render('PaymentPending', [
            'order' => $order,
            'categories' => $categories,
            'phone' => $settings['phone'] ?? '',
            'email' => $settings['email'] ?? '',
            'facebook' => $settings['facebook'] ?? '',
            'instagram' => $settings['instagram'] ?? '',
            'tiktok' => $settings['tiktok'] ?? '',
            'address' => $settings['address'] ?? '',
        ]);
    }

    private function tapEndpointUrl(string $configKey, string $path): string
    {
        return (string) (config('services.tap.' . $configKey)
            ?: rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/'));
    }
}
