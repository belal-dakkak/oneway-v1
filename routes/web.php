<?php

use App\Http\Controllers\Admin\AccessoryController;
use App\Http\Controllers\Admin\ClientDebitController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DebitController;
use App\Http\Controllers\Admin\FabricController;
use App\Http\Controllers\Admin\MerchantCodeController;
use App\Http\Controllers\Admin\MerchantRefundController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductColorController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RefundController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserProductController;
use App\Http\Controllers\Admin\MobileSliderController;
use App\Http\Controllers\Website\ArabicHomeController;
use App\Http\Controllers\Website\FavoriteController;
use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\Website\OrderController as WebsiteOrderController;
use App\Http\Controllers\Website\ContactMessageController;
use App\Http\Controllers\Website\PaymentController;
use App\Mail\TestMail;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use App\Models\Refund;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['country'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('homepage');
    Route::get('/ar', [ArabicHomeController::class, 'index'])->name('homepage.arabic');
    Route::get('/shop', [HomeController::class, 'shop'])->name('shop');
    Route::get('/shop/ar', [ArabicHomeController::class, 'shop'])->name('shop.arabic');
    Route::get('/product/{product}', [HomeController::class, 'product'])->name('product.show');
Route::post('/merchant/verify', [HomeController::class, 'verifyMerchantCode'])->name('merchant.verify');
Route::post('/merchant/disable', [HomeController::class, 'disableMerchantMode'])->name('merchant.disable');
Route::post('/country', [HomeController::class, 'setCountry'])->name('country.set');
    Route::get('/categories', [HomeController::class, 'categories'])->name('categories.web');
    Route::get('/about', [HomeController::class, 'about'])->name('about');
    Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
    Route::get('/coming-soon', [HomeController::class, 'comingSoon'])->name('coming-soon');


    Route::get('/cart', [WebsiteOrderController::class, 'cart'])->name('cart');
    Route::get('/checkout', [WebsiteOrderController::class, 'checkout'])->name('checkout');
    Route::post('/checkout', [WebsiteOrderController::class, 'placeOrder'])->name('order.place');
    Route::get('/order-success/{id}', [WebsiteOrderController::class, 'success'])->name('order.success');
    Route::get('/payment-failed/{id}', [WebsiteOrderController::class, 'paymentFailed'])->name('payment.failed');
    Route::get('/payment-pending/{id}', [WebsiteOrderController::class, 'paymentPending'])->name('payment.pending');

    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites/toggle', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    Route::post('/contact-messages', [ContactMessageController::class, 'store'])->name('contact-messages.store');

    // Legal & Policy Pages
    Route::get('/privacy-policy', [HomeController::class, 'privacyPolicy'])->name('privacy-policy');
    Route::get('/terms-and-conditions', [HomeController::class, 'termsAndConditions'])->name('terms-and-conditions');
    Route::get('/shipping-policy', [HomeController::class, 'shippingPolicy'])->name('shipping-policy');
    Route::get('/refund-policy', [HomeController::class, 'refundPolicy'])->name('refund-policy');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/profile', [\App\Http\Controllers\Website\ProfileController::class, 'show'])->name('website.profile');
        Route::post('/profile/update', [\App\Http\Controllers\Website\ProfileController::class, 'update'])->name('website.profile.update');
    });
});

// Tap does not carry the storefront country session. Keep gateway endpoints
// independent from country detection and verify every charge server-to-server.
Route::get('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');
Route::post('/payment/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');

Route::get('testorder/{id}', function ($id) {
    $user = User::where('email', 'mouhabshalabi@gmail.com')->where('deleted', 0)->first();
    /* return response()->json($user); */

    $order        = Order::find($id);
    $invoice_date = date('jS F Y', strtotime($order->invoice_date));
    $language     = 'en';
    $country      = \App\Support\Country::id();
    $settings     = Setting::where('country', $country)->where('language', $language)->pluck('value', 'name')->toArray();
    $items        = $order->items();

    return view('includes.invoice_template', compact('order', 'settings', 'items'));
});
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->middleware(['guard:admin|warehouse|shop|merchant|shipper'])
    ->prefix('admin')
    ->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::middleware(['auth:sanctum'])
    ->prefix('admin')
    ->group(function () {
    Route::name('productColors.')
        ->prefix('/productColors')
        ->middleware(['guard:admin|warehouse'])
        ->group(function () {
            Route::resource('/', '\App\Http\Controllers\Admin\ProductColorController', ['parameters' => ['' => 'productColor']]);
            Route::get('/single-print/{id}', [ProductColorController::class, 'singlePrint'])->name('print');
        });

    Route::name('products.')
        ->prefix('/products/products')
        ->middleware(['guard:admin|warehouse'])
        ->group(function () {
            Route::post('/{product}/toggle-merchant-visibility', [ProductController::class, 'toggleMerchantVisibility'])->name('toggleMerchantVisibility');
            Route::resource('/', '\App\Http\Controllers\Admin\ProductController', ['parameters' => ['' => 'product']]);
            Route::post('/print-products', [ProductColorController::class, 'print'])->name('print');
            Route::post('/print-single-product', [ProductColorController::class, 'singlePrint'])->name('printSingle');
        });

    Route::name('colors.')
        ->prefix('/colors')
        ->middleware(['guard:admin|warehouse'])
        ->group(function () {
            Route::resource('/', '\App\Http\Controllers\Admin\ColorController', ['parameters' => ['' => 'color']]);
        });

    Route::name('users.')
        ->prefix('/users')
        ->middleware(['guard:admin|warehouse'])
        ->group(function () {
            Route::resource('/', '\App\Http\Controllers\Admin\UserController', ['parameters' => ['' => 'user']]);
            Route::get('/wallet/close/{id}', [UserController::class, 'closeWallet'])->name('wallet.close');
        });

    Route::name('categories.')
        ->prefix('/categories')
        ->middleware(['guard:admin|warehouse'])
        ->group(function () {
            Route::resource('/', '\App\Http\Controllers\Admin\CategoryController', ['parameters' => ['' => 'category']]);
        });

    Route::name('banners.')
        ->prefix('/banners')
        ->middleware(['guard:admin|warehouse'])
        ->group(function () {
            Route::resource('/', '\App\Http\Controllers\Admin\BannerController', ['parameters' => ['' => 'banner']]);
        });

    Route::name('branches.')
        ->prefix('/branches')
        ->middleware(['guard:admin'])
        ->group(function () {
            Route::resource('/', '\App\Http\Controllers\Admin\BranchController', ['parameters' => ['' => 'branch']]);
        });

    Route::name('sliders.')
        ->prefix('/sliders')
        ->middleware(['guard:admin|warehouse'])
        ->group(function () {
            Route::resource('/', '\App\Http\Controllers\Admin\SliderController', ['parameters' => ['' => 'slider']]);
        });

    Route::name('mobile-sliders.')
        ->prefix('/mobile-sliders')
        ->middleware(['guard:admin|warehouse'])
        ->group(function () {
            Route::resource('/', '\App\Http\Controllers\Admin\MobileSliderController', ['parameters' => ['' => 'mobileSlider']]);
        });



    Route::name('expenses.')
        ->prefix('/expenses')
        ->group(function () {
            Route::resource('/', '\App\Http\Controllers\Admin\ExpenseController', ['parameters' => ['' => 'expense']]);
        });

    Route::name('cuts.')
        ->prefix('/cuts')
        ->group(function () {
            Route::resource('/', '\App\Http\Controllers\Admin\CutController', ['parameters' => ['' => 'cut']]);
        });

    Route::name('fabrics.')
        ->prefix('/fabrics')
        ->group(function () {
            Route::resource('/', '\App\Http\Controllers\Admin\FabricController', ['parameters' => ['' => 'fabric']]);
            Route::post('/exports/{fabric}', [FabricController::class, 'exports'])->name('exports');
            Route::get('/logs/{fabric}', [FabricController::class, 'logs'])->name('logs');
        });

    Route::name('accessories.')
        ->prefix('/accessories')
        ->group(function () {
            Route::resource('/', '\App\Http\Controllers\Admin\AccessoryController', ['parameters' => ['' => 'accessory']]);
            Route::post('/exports/{accessory}', [AccessoryController::class, 'exports'])->name('exports');
            Route::get('/logs/{accessory}', [AccessoryController::class, 'logs'])->name('logs');
        });

    Route::name('debits.')
        ->prefix('/debits')
        ->group(function () {
            Route::resource('/', '\App\Http\Controllers\Admin\DebitController', ['parameters' => ['' => 'debit']]);
            Route::get('/pay-debit/{id}', [DebitController::class, 'pay'])->name('pay');
            Route::post('/add-payment', [DebitController::class, 'addPayment'])->name('payment');
            Route::get('/merchants/all', [DebitController::class, 'merchants'])->name('merchants');
            Route::get('/merchants/{id}', [DebitController::class, 'payments'])->name('merchants.show');
            Route::get('/log/{id}', [DebitController::class, 'log'])->name('log');
        });

    Route::name('clientDebit.')
        ->prefix('/clientDebit')
        ->group(function () {
            Route::get('/clients/all', [ClientDebitController::class, 'clients'])->name('clients');
            Route::get('/clients/{id}', [ClientDebitController::class, 'payments'])->name('clients.show');
            Route::get('/log/{id}', [ClientDebitController::class, 'log'])->name('log');
            Route::post('/add-payment', [ClientDebitController::class, 'addPayment'])->name('payment');
            Route::post('/add-withdraw', [ClientDebitController::class, 'addWithdraw'])->name('withdraw');
            Route::post('/close-account', [ClientDebitController::class, 'close'])->name('close');
        });

    Route::name('refunds.')
        ->prefix('/refunds')
        ->group(function () {
            Route::resource('/', '\App\Http\Controllers\Admin\RefundController', ['parameters' => ['' => 'refund']]);
            Route::post('/match-product', [RefundController::class, 'match'])->name('match');
        });

    Route::name('merchantRefunds.')
        ->prefix('/merchantRefunds')
        ->group(function () {
            Route::resource('/', '\App\Http\Controllers\Admin\MerchantRefundController', ['parameters' => ['' => 'merchantRefund']]);
            Route::post('/client/refund', [MerchantRefundController::class, 'clientRefund'])->name('client');
        });


    Route::name('userProducts.')
        ->prefix('/userProducts')
        ->middleware(['guard:admin|warehouse'])
        ->group(function () {
            Route::resource('/', '\App\Http\Controllers\Admin\UserProductController', ['parameters' => ['' => 'userProduct']]);
            Route::get('/all/products', [UserProductController::class, 'all'])->name('all');
            Route::post('/match-product', [UserProductController::class, 'match'])->name('match');
            Route::get('/merchant/match', [UserProductController::class, 'forMerchant'])->name('merchant');
        });

    Route::name('orders.')
        ->prefix('/orders')
        ->middleware(['guard:admin|shop|warehouse'])
        ->group(function () {
            Route::get('website-orders', [OrderController::class, 'websiteOrders'])->name('websiteOrders');
            Route::post('website-orders/change-status/{id}', [OrderController::class, 'changeWebsiteOrderStatus'])->name('websiteOrders.changeStatus');
            Route::post('website-orders/mark-paid/{id}', [OrderController::class, 'markWebsiteOrderPaid'])->name('orders.websiteOrders.markPaid');
            Route::resource('/', '\App\Http\Controllers\Admin\OrderController', ['parameters' => ['' => 'order']]);

            Route::get('monthly/orders', [OrderController::class, 'monthly_orders'])->name('monthly_orders');


            Route::get('/simple/create-order', [OrderController::class, 'simpleCreate'])->name('simple');
            Route::get('/complex/create-order', [OrderController::class, 'complexCreate'])->name('complex');
            Route::get('/complex/multi-order', [OrderController::class, 'multiCreate'])->name('multi');
            Route::get('/items/{id}', [OrderController::class, 'getItems'])->name('items');
            Route::post('/match-product', [OrderController::class, 'match'])->name('match');
            Route::post('/multi-match-product', [OrderController::class, 'multiMatch'])->name('multi.match');
            Route::post('/add-payment', [OrderController::class, 'addPayment'])->name('payment');
            Route::get('/all/debts', [OrderController::class, 'debts'])->name('debts');
            Route::get('/all/profits', [OrderController::class, 'profits'])->name('profits');
            Route::post('/single-print/{id}', [OrderController::class, 'singlePrint'])->name('print');
            Route::get('/{id}/print-info', [OrderController::class, 'show'])->name('print-info');
            Route::post('/change-status/{id}', [OrderController::class, 'changeStatus'])->name('change');
            Route::get('/client/products', [OrderController::class, 'forClient'])->name('client');
            Route::get('/app/orders', [OrderController::class, 'appOrders'])->name('appOrders');

        });
    Route::name('settings.')
        ->prefix('/settings')
        ->middleware(['guard:admin|warehouse'])
        ->group(function () {
            Route::get('/', '\App\Http\Controllers\SettingController@index')->name('index');
            Route::post('/', '\App\Http\Controllers\SettingController@store')->name('store');
            Route::get('/edit/{language}', '\App\Http\Controllers\SettingController@edit')->name('edit');
            Route::post('update-store-tax-ratio', '\App\Http\Controllers\SettingController@update_store_tax_ratio')->name('update-store-tax-ratio');
        });
    Route::name('currencies.')
        ->prefix('/currencies')
        ->middleware(['guard:admin'])
        ->group(function () {
            Route::get('/', '\App\Http\Controllers\CurrencyController@index')->name('index');
            Route::post('/', '\App\Http\Controllers\CurrencyController@store')->name('store');
        });
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notification.index');
    Route::get('/show-notification', [NotificationController::class, 'showNotification'])->name('notification.show');
    Route::get('/show-order-notification', [NotificationController::class, 'showOrderNotification'])->name('notification.order.show');
    Route::get('/approve-notification/{id}', [NotificationController::class, 'approveNotification'])->name('notification.approve');

    Route::post('/media', [ProductController::class, 'storeMedia'])->name('media');

    // Merchant Codes
    Route::prefix('merchant-codes')->name('admin.merchantCodes.')->group(function () {
        Route::get('/', [MerchantCodeController::class, 'index'])->name('index');
        Route::post('/generate', [MerchantCodeController::class, 'generate'])->name('generate');
        Route::post('/{merchantCode}/toggle', [MerchantCodeController::class, 'toggle'])->name('toggle');
        Route::delete('/{merchantCode}', [MerchantCodeController::class, 'destroy'])->name('destroy');
    });

    Route::name('contactMessages.')
        ->prefix('/contact-messages')
        ->middleware(['guard:admin'])
        ->group(function () {
            Route::get('/', [ContactMessageController::class, 'index'])->name('index');
            Route::patch('/{contactMessage}', [ContactMessageController::class, 'update'])->name('update');
            Route::delete('/{contactMessage}', [ContactMessageController::class, 'destroy'])->name('destroy');
        });
});

Route::view('/privacy', 'privacy')->name('privacy');

Route::get('/db-seed', function () {
    Artisan::call("schedule:run");
});

Route::get('/test-mail', function () {
    $token = rand(100000, 999999);
    $message = 'Your activation code is ' . $token;
    $data = [
        'name' => 'Bilal',
        'message' => $message
    ];
    //
    //    Mail::to([
    //        'email' => 'theoneway.fashion@gmail.com'
    //    ])->send(new VerifyYourAccount($data));
});
Route::get('/invoice/download/{source}/{id}', [OrderController::class, 'typedDownloadInvoice'])
    ->where('source', 'order|website')->name('download.invoice.typed');
Route::get('/invoice/print-v2/{source}/{id}', [OrderController::class, 'typedPrintInvoice'])
    ->where('source', 'order|website')->name('invoice.typed.printv2');
Route::get('/invoice/view/{source}/{id}', [OrderController::class, 'typedInvoice'])
    ->where('source', 'order|website')->name('invoice.typed.show');

Route::get('/invoice/download/{id}', [OrderController::class, 'download_invoice'])->name('download.invoice.show');

Route::get('/invoice/print-v2/{id}', [OrderController::class, 'print_invoice_v2'])->name('invoice.printv2');

Route::get('/invoice/{id}', [OrderController::class, 'invoice'])->name('invoice.show');
Route::get('/app-invoice/{id}', [OrderController::class, 'appInvoice'])->name('app.invoice.show');
Route::get('/invoice/shipper/{id}', [OrderController::class, 'invoiceShipper'])->name('invoice.shipper.show');
Route::get('/merchant/account/{id}', [DebitController::class, 'merchantAccountLog'])->name('merchant.account.log');
Route::get('/client/account/{id}', [ClientDebitController::class, 'clientAccountLog'])->name('client.account.log');
Route::get('/send/message/{id}/{number}', function ($id, $number) {
    return view('wa', compact('id', 'number'));
});
Route::middleware(['guard:admin'])->group(function () {
    Route::get('/export', [OrderController::class, 'createPDF'])->name('exportpdf');
    Route::get('/export2', [OrderController::class, 'createPDF2'])->name('exportpdf2');

});

Route::get('language/{language}', function ($language) {
    Session()->put('locale', $language);
    return redirect()->back();
})->name('language');

Route::get('country/{id}', function ($country) {
    if (!in_array((int) $country, \App\Support\Country::allowedIds(), true) || auth()->user()->role_id != 1) return redirect()->back();
    User::where('id', auth()->user()->id)->update(['country_id' => $country]);
    return redirect()->back();
})->name('country');



Route::get('update-data-v1',function() {

    // $order_items = OrderItem::get();

    // foreach($order_items as $item) {
    //     if($item->order) {
    //         $item->update([
    //             'item_price_paid'        => ($item->order->curr_rate * $item->item_price),
    //             'total_price_paid'       => ($item->order->curr_rate * $item->total_price),
    //             'tax_value_paid'         => ($item->order->curr_rate * $item->tax_value),
    //             'price_without_tax_paid' => ($item->order->curr_rate * $item->price_without_tax),
    //         ]);
    //     }
    // }

    $refunds = Refund::get();

    foreach($refunds as $refund) {
        if($refund->orderItem && $refund->orderItem->order) {
            $refund->update([
                'total_price_paid' => ($refund->orderItem->order->curr_rate * $refund->total_price),
            ]);
        }
    }

    dd('ok');

});


Route::get('/run/clear', function () {

    $results = [];

    $results['config:clear'] = Artisan::call('config:clear');
    $results['config:cache'] = Artisan::call('config:cache');
    $results['route:clear']  = Artisan::call('route:clear');
    $results['view:clear']   = Artisan::call('view:clear');
    $results['cache:clear']  = Artisan::call('cache:clear');

    return response()->json([
        'status' => 'ok',
        'results' => $results,
    ]);
});

//Route::get('run/storage-link', function () {
//
//    if (! file_exists(public_path('storage'))) {
//        Artisan::call('storage:link');
//    }
//
//    return response()->json([
//        'status' => 'ok',
//        'storage_linked' => file_exists(public_path('storage')),
//        'path' => public_path('storage'),
//    ]);
//});

//Route::get('run/migrate', function () {
//
//    Artisan::call('migrate');
//
//    return response()->json([
//        'status' => 'ok',
//        'path' => public_path('storage'),
//    ]);
//});
//
//Route::get('prices/fix', function () {
//
//    $result = DB::table('products')
//        ->whereNotNull('sale_price')
//        ->whereColumn('sale_price', '>', 'retail_price')
//        ->update([
//            'sale_price' => DB::raw('ROUND(sale_price / 3.67, 2)')
//        ]);
//
//    return response()->json([
//        'status' => 'ok',
//        'result' => $result,
//    ]);
//});

Route::get('test-mail', function (){
    Mail::to('ya7ya.alnajjar@gmail.com')
        ->send(new TestMail());
});
