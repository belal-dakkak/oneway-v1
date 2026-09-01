<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductSize;
use App\Models\ShippingDetails;
use App\Models\User;
use App\Models\UserProductLog;
use App\Models\WebsiteOrder;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller

{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'direction' => ['in:asc,desc', 'nullable'],
            'field' => ['in:id', 'nullable']
        ]);

        $user = User::query()->find(auth()->id());
        $userNotifications = $user->notifications()->latest()->get();

        if ($request->wantsJson()){
            return $userNotifications;
        }

        return Inertia::render('Admin/Notification/Index', [
            'userNotifications' => $userNotifications,
            'filters' => $request->all(['search', 'field', 'direction'])
        ]);
    }

    public function showNotification(Request $request)
    {

        //$table = $request->get('table');
        $notificationId = $request->get('notification');

        $notificationRow = DatabaseNotification::find($notificationId);

        if($notificationRow && $notificationRow->data) {
            $table = $notificationRow->data['table'];
        } else {
            $table = [];
        }

        $user = auth()->user();
        $notification = $user->unreadNotifications()->whereId($notificationId);

        $notification->update(['read_at' => Carbon::now()]);

        $productColor = $table['product_color'];
        $product = $table['product_color']['product'];
        $log = UserProductLog::query()->where('user_product_id', $table['id'])->orderByDesc('id')->first();


        if($notificationRow && $notificationRow->data) {
            $logsArr = ($notificationRow->data)['logsArr'];
            if(! empty($logsArr)) {
                foreach($logsArr as $index => $arr) {
                    $logsArr[$index]['log'] = UserProductLog::query()->where('id', $arr['user_product_log_id'])->first();
                }
            }
        } else {
            $logsArr = null;
        }

        $data = [
            'userProduct' => $table,
            'productColor' => $productColor,
            'product' => $product,
            'logs' => $logsArr,
            'log' => $log
        ];

        return Inertia::render('Admin/Notification/Show', $data);
    }

    public function showOrderNotification(Request $request)
    {
        $table = $request->get('table');
        $notificationId = $request->get('notification');

        $user = auth()->user();
        $notification = $user->unreadNotifications()->whereId($notificationId);

        $notification->update(['read_at' => Carbon::now()]);

        $id = $table['id'];

        // Try to find as WebsiteOrder first, then fall back to Order
        $order = WebsiteOrder::query()->find($id);
        if (!$order) {
            $order = Order::query()->find($id);
        }

        if (!$order) {
            return redirect()->back()->with('error', 'Order not found');
        }

        // Handle case where order might not have shippingDetails
        $shippingDetails = $order->shippingDetails ?? null;

        $isWebsiteOrder = $order instanceof WebsiteOrder;

        // Get items based on order type
        if ($isWebsiteOrder) {
            $items = $order->items();
        } else {
            $items = $order->productItems();
        }

        $buyer = transformItemForVue($order->buyer, User::class);
        $order = transformItemForVue($order, get_class($order));

        if (!$isWebsiteOrder) {
            if ($shippingDetails && $shippingDetails->city) {
                $city = transformItemForVue($shippingDetails->city, City::class);
            } else {
                $city = null;
            }
            $shippingDetails = $shippingDetails ? transformItemForVue($shippingDetails, ShippingDetails::class) : null;

        } else {
            $city = new \stdClass();
            $city->name = $order['city'] ?? null;

            $shippingDetails = new \stdClass();
            $shippingDetails->address = $order['address'];
            $shippingDetails->apartment = $order['flat_number'];
            $shippingDetails->building = $order['building_name'];

        }




//        dd($isWebsiteOrder);

        $data = [];
        foreach ($items->get() as $item){
            $product = $item->product;
            $productSize = ProductSize::query()
                ->where('product_color_id', $product->id)
                ->where('product_id', $product->product_id)
                ->first();

            $data[] = (object)[
                'name' => $product->simple_name,
                'qty' => $item->qty,
                'item_price' => $item->item_price,
                'total_price' => $item->total_price,
                'barcode' => $productSize ? $productSize->barcode : '',
                'size' => $item->size,
            ];
        }

        return Inertia::render('Admin/Orders/View', [
            'order' => $order,
            'buyer' => $buyer,
            'items' => $data,
            'address' => $shippingDetails,
            'city' => $city
        ]);
    }

    public function approveNotification($id): RedirectResponse
    {
        $log = UserProductLog::query()->find($id);
        $log->update(['approved' => 1]);
        return Redirect::route('notification.index');
    }

}
