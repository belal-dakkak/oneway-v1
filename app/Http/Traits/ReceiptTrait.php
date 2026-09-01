<?php

namespace App\Http\Traits;

use App\Models\Currency;
use App\Models\Order;
use App\Models\WebsiteOrder;
use App\Models\ProductColor;
use App\Models\UserProduct;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use niklasravnsborg\LaravelPdf\Facades\Pdf;


trait ReceiptTrait {

    /**
     *  generate PDF coupon.
     */
    public function generatePDF($orderId)
    {
        $result = $this->getExportData($orderId);

        $order = WebsiteOrder::find($orderId) ?: Order::find($orderId);

        if (!$result)
            return redirect(route('dashboard'));

        $pdf = PDF::loadView('receipts.pdfReceipt', [
            'items' => $result['items'],
            'order' => $order,
        ]);
        $fileName = 'orders/pdf/'.$orderId.'/'.generateRandomString().'.pdf';
        return Storage::disk('public')->put($fileName, $pdf->output()) ? $fileName : false;
    }

    private function getExportData($orderId): ?array
    {

        $order = WebsiteOrder::find($orderId) ?: Order::find($orderId);

        if(auth()->check() && auth()->user()->country_id == 2){
            $rate = Currency::where('name','aed')->first()->rate;
        }else{
			$rate = Currency::where('name','aed')->first()->rate;
            //$rate = 1;
        }

        if (!$order->sent_at)
            $order->update(['sent_at' => Carbon::now()]);

        if ($order instanceof WebsiteOrder || $order->type == Order::TYPE_APP){
            $items = $order instanceof WebsiteOrder ? $order->items() : $order->productItems();
            $items = $items->selectRaw('SUM(qty) as qty, MAX(item_price) as item_price, MAX(total_price) as total_price, product_color_id, tax_value , price_without_tax_paid , tax_value_paid')->groupBy('product_color_id')->get();
            $products = array();
            foreach ($items as $item) {
                $product = ProductColor::find($item->product_color_id);
                if(!array_key_exists($product->product_id,$products))
                    $products[$product->product_id] = ['name' => $product->product->name." - ".$product->product->barcode, 'qty' => $item->qty, 'item_price' => $item->item_price, 'total_price' => $item->total_price, 'tax_value' => $item->tax_value, 'tax_value_paid' => $item->tax_value_paid, 'price_without_tax_paid' => $item->price_without_tax_paid];
                else
                    $products[$product->product_id]['qty'] += $item->qty;
            }
            $data = [];

            foreach ($products as $item){
                $item = (object) $item;
                $data[] = (object)[
                    'name' => $item->name,
                    'qty' => $item->qty,
                    'tax_value' => $item->tax_value,
                    'tax_value_paid' => $item->tax_value_paid,
                    'price_without_tax_paid' => $item->price_without_tax_paid,
                    'item_price' =>  number_format((float)round($item->item_price* $rate), 2, '.', ''),
                    'total_price' => number_format((float)round($item->total_price * $rate), 2, '.', ''),
                ];
            }
            $items = collect($data);
        }else{

            $items    = $order->items();

            $products = $order->items()->pluck('user_product_id')->toArray();
            $products = UserProduct::selectRaw('GROUP_CONCAT(id) as ids, product_color_id')->whereIn('id',$products)->groupBy('product_color_id')->get();
            $data = [];
            $productss = array();

            foreach ($products as $item){

                // $order_item = Order::query()->find($orderId)->items()->selectRaw('SUM(qty) as qty, MAX(item_price) as item_price, MAX(total_price) as total_price, tax_value , price_without_tax_paid , tax_value_paid')->whereIn('user_product_id',explode(',',$item->ids))->first();

                // $order_item = Order::query()
                // ->find($orderId)
                // ->items()
                // ->selectRaw('SUM(qty) as qty, MAX(item_price) as item_price, MAX(total_price) as total_price, tax_value, price_without_tax_paid')
                // ->whereIn('user_product_id', explode(',', $item->ids))
                // ->groupBy('tax_value', 'price_without_tax_paid')  // Add missing group by
                // ->first();

                $order_item = Order::query()
                ->find($orderId)
                ->items()
                ->selectRaw('SUM(qty) as qty, MAX(item_price) as item_price, MAX(total_price) as total_price, MAX(tax_value) as tax_value, MAX(tax_value_paid) as tax_value_paid, MAX(price_without_tax_paid) as price_without_tax_paid')
                ->whereIn('user_product_id', explode(',', $item->ids))
                ->first();


                $product = ProductColor::find($item->product_color_id);

                if(!array_key_exists($product->product_id,$productss))

                    $productss[$product->product_id] = [
						'name' => $product->product->name." - ".$product->product->barcode,
						'qty' => $order_item->qty,
                        'tax_value' => $order_item->tax_value,
                        'tax_value_paid' => $order_item->tax_value_paid,
                        'price_without_tax_paid' => $order_item->price_without_tax_paid,
						'item_price' => round($order_item->item_price* $rate),
						'total_price' => round($order_item->total_price* $rate)
					];

                else
                    $productss[$product->product_id]['qty'] += $order_item->qty;
            }
            $data = [];


            foreach ($productss as $item){
                $item = (object) $item;
                $data[] = (object)[
                    'name' => $item->name,
                    'qty' => $item->qty,
                    'tax_value' => $item->tax_value,
                    'tax_value_paid' => $item->tax_value_paid,
                    'price_without_tax_paid' => $item->price_without_tax_paid,
                    'item_price' => $item->item_price,
                    'total_price' => $item->total_price,
                ];
            }
            $items = collect($data);

        }
        return compact('order','items');
    }
}
