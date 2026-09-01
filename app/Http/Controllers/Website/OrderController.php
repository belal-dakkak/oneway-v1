<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\WebsiteOrder;
use App\Models\Setting;
use App\Models\User;
use App\Rules\ValidPhone;
use App\Repositories\OrderRepository;
use App\Services\Payment\TapPaymentService;
use App\Jobs\NotificationOrderJob;
use App\Mail\NewOrderAdminEmail;
use App\Mail\OrderConfirmationEmail;
use App\Services\CurrencyService;
use App\Support\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    private $orderRepository;
    private $tapService;
    private $currencyService;

    public function __construct(OrderRepository $orderRepository, TapPaymentService $tapService, CurrencyService $currencyService)
    {
        $this->orderRepository = $orderRepository;
        $this->tapService = $tapService;
        $this->currencyService = $currencyService;
    }

    public function cart(): Response
    {
        $categories = Category::limit(6)->get();
        $categories = transformDataForVue($categories);

        $country = Country::id();
        $language = 'en';
        $settings = Setting::where('country', $country)->where('language', $language)->pluck('value', 'name')->toArray();

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
        $settings = Setting::where('country', $country)->where('language', $language)->pluck('value', 'name')->toArray();

        return Inertia::render('Checkout', [
            'categories' => $categories,
            'phone' => $settings['phone'] ?? '',
            'email' => $settings['email'] ?? '',
            'facebook' => $settings['facebook'] ?? '',
            'instagram' => $settings['instagram'] ?? '',
            'tiktok' => $settings['tiktok'] ?? '',
            'address' => $settings['address'] ?? '',
        ]);
    }

    public function placeOrder(Request $request)
    {

        $request->validate([
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
            'currency' => 'required|string',
        ]);

        $countryId = Country::id();
        try {
            $currencyCode = $this->currencyService->validateForCountry($request->currency, $countryId, true);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['currency' => 'العملة المختارة غير متاحة لهذا البلد.']);
        }
        if ($countryId === User::COUNTRY_SYRIA && $request->payment_method !== 'cod') {
            return back()->withErrors(['payment_method' => 'الدفع الإلكتروني غير متاح لطلبات سوريا.']);
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

        // We need to set auth user if they are logged in, or use a guest user?
        // Guest user management: Find or create user by email
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Str::random(12),
                'role_id' => User::ROLE_CLIENT,
                'country_id' => $countryId,
            ]);

            // Initial wallet for new user
            $user->wallet()->create(['credit' => 0, 'debit' => 0]);
        }
        auth()->login($user);

        $order = $this->orderRepository->addForOnline(new Request($orderRequestData));

        if (!$order instanceof WebsiteOrder) {
            return back()->with('error', 'Failed to place order: ' . $order);
        }

        if ($request->payment_method === 'card') {
            // Clean phone number: strip country codes and leading zeros for Tap
            $cleanPhone = $request->phone;
            $cleanPhone = preg_replace('/^(\+971|00971|971|\+961|00961|961|\+963|00963|963)/', '', $cleanPhone);
            $cleanPhone = ltrim($cleanPhone, '0');
            $cleanPhone = preg_replace('/\D/', '', $cleanPhone); // Remove any remaining non-digits

            $amount = round((float)$order->total_price, 2);
            $currency = $order->curr_type;

            if ($amount <= 0) {
                $order->delete();
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
                'redirect' => ['url' => route('payment.callback')],
                'post' => ['url' => route('payment.webhook')],
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

            // Cleanup order if payment initiation fails
            $order->delete();
            $errorMessage = $charge['errors'][0]['description'] ?? 'Payment gateway error. Please try again.';
            return back()->withErrors(['payment' => $errorMessage]);
        }

        // For COD payments, dispatch notifications immediately
        $order->dispatchNotifications();
        return redirect()->route('order.success', ['id' => $order->id]);
    }

    public function success($id): Response
    {
        $order = WebsiteOrder::findOrFail($id);
        $categories = Category::limit(6)->get();
        $categories = transformDataForVue($categories);

        $country = $order->country_id;
        $language = 'en';
        $settings = Setting::where('country', $country)->where('language', $language)->pluck('value', 'name')->toArray();

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
        $settings = Setting::where('country', $country)->where('language', $language)->pluck('value', 'name')->toArray();

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
}
