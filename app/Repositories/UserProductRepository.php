<?php

namespace App\Repositories;

use App\Jobs\NotificationJob;
use App\Models\Debit;
use App\Models\DebitLog;
use App\Models\MerchantDebit;
use App\Models\ProductColor;
use App\Models\User;
use App\Models\UserProduct;
use App\Models\UserProductLog;
use App\Notifications\ShopNotification;
use App\Services\CurrencyService;
use App\Support\Country;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Jenssegers\Date\Date;
use Illuminate\Support\Facades\Cache;

class UserProductRepository
{
    private function clearHomeCache()
    {
        foreach (Country::allowedIds() as $countryId) {
            foreach (['home_new_products_', 'home_offer_products_', 'home_random_products_'] as $prefix) {
                Cache::forget($prefix.$countryId);
                Cache::forget($prefix.$countryId.'_m');
            }
        }
    }

    public function add(Request $request)
    {
        $currencyCode = Country::defaultCurrency(auth()->user()->country_id);
        $rate = app(CurrencyService::class)->rate($currencyCode);

        $lsizes = json_decode($request->get('lsizes'),true);
        $length = count($lsizes);

        $userProduct = null;
        $note = null;

        $retailPrice = 0;
        $shop_id = 0;
        $wholesalePrice = 0;
        $logsArr = [];

        $merchantDebit = null;

        $productColorId = 0;

        for($i = 0; $i < $length; $i++) {

            $psize = $lsizes[$i];

            // for($request->get('lsizes') as $psize) {
            if($request->has('travel')){
                $userId = $request->get('user');
                $productColorId = $request->get('product');
            }else{
                $userId = $request->get('user')['id'];
                $productColorId = isset($request->get('product')['product_color_id'])? $request->get('product')['product_color_id']: $request->get('product')['id'];
            }

            if($psize['stock'] > 0){

                // $userId = $request->get('user');
                // $productColorId = $request->get('product');
                $stock = $psize['stock'];
                $size = $psize['size'];
                $barcode =$psize['barcode'];
                $user = User::find($userId);

                // dd($barcode);

                //$merchant_id = $psize['merchant_id'];

                $exists = UserProduct::query()
                    ->where('user_id', $userId)
                    ->where('product_color_id', $productColorId)
					->where('barcode' , $barcode)
                    ->where('size' ,$size )->first();

                $productColor = ProductColor::where('sizes','LIKE','%'.$barcode.'%')->first();

                $size_exist = false;

                $stock_available = false;

                if($productColor){
                    if(!is_null($productColor->sizes)){
                        $sizes = json_decode($productColor->sizes,true);
                        if(!is_null($sizes)){
                            if(count($sizes) > 0){
                                $available_sizes = array_column($sizes, 'size');
                                $size_exist = in_array($size,$available_sizes);
                                if($size_exist){
                                    $index = array_search($size, $available_sizes);
                                    if($stock <= $sizes[$index]['stock']){
                                        // $sizes[$index]['stock'] = $sizes[$index]['stock'] - $stock;
                                        // $productColor->sizes = json_encode($sizes);
                                        $stock_available = true;
                                    }
                                }
                            }
                        }
                    }
                }



                // if(!$size_exist) return "nosize";
                // if(!$stock_available) return "nostock";
                if ($request->has('travel'))
                {
                    if ($request->has('is_warehouse')){
                        if (auth()->user()->role_id === User::ROLE_WAREHOUSE){
                            $myItem = UserProduct::query()->where('user_id', auth()->id())
                                ->where('product_color_id', $productColorId)
                                //->where('merchant_id',$merchant_id)
                                ->where('stock','>',0)
                                ->where('size' ,$size )->first();
                            $myItemStock = $myItem->stock;
                            //dd('aa',$myItem->id,$myItemStock);
                            $myItem->update(['stock' => $myItemStock - $stock]);
                        }
                    }
                }else{
                    if (auth()->user()->role_id === User::ROLE_WAREHOUSE){
                        $myItem = UserProduct::query()->where('user_id', auth()->id())
                            ->where('product_color_id', $productColorId)
                            //->where('merchant_id',$merchant_id)
                            ->where('stock','>',0)
                            ->where('size' ,$size )->first();
                        $myItemStock = $myItem->stock;
                        $myItem->update(['stock' => $myItemStock - $stock]);
                    }
                }


                if ($exists){
                    $copy = clone $exists;
                    $oldStock = $copy->stock;
                }else{
                    $oldStock = 0;
                }


                $retailPrice = $request->get('retail_price');
                $wholesalePrice = $request->get('wholesale_price');



                $userProduct = UserProduct::query()->updateOrCreate([
                    'user_id' => $userId,
                    'product_color_id' => $productColorId,
                    'size' => $size,
                    'country_id' => $user->country_id,
                    'barcode' => $barcode
                ],[
                    'user_id' => $userId,
                    'product_color_id' => $productColorId,
                    'size' => $size,
                    'country_id' => $user->country_id,
                    'barcode' => $barcode,
                    'stock' => $oldStock + $stock,
                    'retail_price' => $retailPrice,
                    'wholesale_price' => $wholesalePrice,
                    'price_before_discount' => $request->get('price_before_discount'),
                    'merchant_id' => $request->get('merchant')
                ]);

                $userProduct->ProductColor->product->update(['retail_price' => $retailPrice]);

                Date::setLocale('ar');

                $date = Date::parse($userProduct->updated_at)->timezone('Asia/Beirut')->format('d-m-Y h:i a');
                $shop = $userProduct->user->name;
                $shop_id = $userProduct->user->id;
                $item = $userProduct->productColor->product_name;

                //$note = "قام المستودع بإرسال $stock من المنتج $item إلى المحل $shop بتاريخ $date ";
                $note = "قام المستودع بإرسال $stock من المنتج $item من المقاس $size  الى المحل $shop بتاريخ $date";

                //$note = '';
                $new_price1 = number_format(round($retailPrice * $rate),2,",","");
                $note .= "بسعر  $new_price1 للتجزئة ";

                $log = UserProductLog::query()->create([
                    'user_product_id' => $userProduct->id,
                    'note' => $note
                ]);

                if ($creditor = $request->get('merchant')){

                    $amount = $wholesalePrice * $stock;
                    Debit::query()->create([
                        'creditor_id' => $creditor,
                        'debtor_id' => $userId,
                        'amount' => $amount,
                        'user_product_id' => $userProduct->id,
                        'user_product_log_id' => $log->id
                    ]);

                    $userProduct->user->wallet->update(['debit' => DB::raw("debit + $amount")]);
                    User::query()->find($creditor)->wallet->update(['credit' => DB::raw("credit + $amount")]);

                    $merchantDebit = MerchantDebit::query()->firstOrCreate([
                        'creditor_id' => $creditor,
                        'debtor_id' => $userProduct->user_id
                    ],[
                        'creditor_id' => $creditor,
                        'debtor_id' => $userProduct->user_id,
                        'amount' => $amount
                    ]);

                    if (!$merchantDebit->wasRecentlyCreated)
                        $merchantDebit->update(['amount' => DB::raw("amount + $amount")]);

                    //$note = "قام المستودع بإرسال $stock من المنتج $item إلى المحل $shop بتاريخ $date ";
                    $note = "قام المستودع بإرسال $stock من المنتج $item من المقاس $size  الى المحل $shop بتاريخ $date";

                    //$note .= "بسعر $wholesalePrice";
                    //$note .= "بسعر $new_price1";

                    // old - new
                    // DebitLog::query()->create([
                    //     'merchant_debit_id' => $merchantDebit->id,
                    //     'user_product_id' => $userProduct->id,
                    //     'amount' => $amount,
                    //     'note' => $note
                    // ]);
                }

                $logsArr[] = [
                    'user_product_id'     => $userProduct->id,
                    'user_product_log_id' => $log->id,
                    'note'                => $note,
                    'stock'               => $stock,
                    'size'                => $size,
                ];


                // $shopAccount = $userProduct->user;
                // $user = auth()->user();
                // $admin = User::query()->where('role_id', User::ROLE_ADMIN)->first();

                // NotificationJob::dispatch($shopAccount, $userProduct, $note, $user)->onQueue('notify');
                // NotificationJob::dispatch($admin, $userProduct, $note, $user)->onQueue('notify');
                // NotificationJob::dispatch($user, $userProduct, $note, $user)->onQueue('notify');
            }
        }

        //dd($userProduct);

        if($userProduct != null && $note != null) {

            Date::setLocale('ar');

            $date = Date::parse($userProduct->updated_at)->timezone('Asia/Beirut')->format('d-m-Y h:i a');

            $shop = $userProduct->user->name;
            $item = $userProduct->productColor->product_name;

            $stock = array_sum(array_column($lsizes,'stock'));

            $new_price2 = number_format(round($retailPrice * $rate),2,",","");
            $note = "قام المستودع بإرسال $stock من المنتج $item إلى المحل $shop بتاريخ $date ";
            $note .= "بسعر  $new_price2 للتجزئة ";

            $now = Carbon::now()->format('Y-m-d');

            if ($creditor = $request->get('merchant')){

                //$new_price3 = $wholesalePrice * $rate;
                $new_price3 = number_format(round($retailPrice * $rate),2,",","");
                $note = "قام المستودع بإرسال $stock من المنتج $item إلى المحل $shop بتاريخ $date ";
                $note .= "بسعر $new_price3";

                if($merchantDebit && $item) {

                    $check_debit_log = DebitLog::where('product_color_id',$productColorId)->where('shop_id',$shop_id)->where('merchant_id',$request->get('merchant'))->where('request_date',$now)->where('type','store')->first();

                    if($check_debit_log) {

                        $stock = $stock + $check_debit_log->qty;
                        $debit_note = "قام المستودع بإرسال $stock من المنتج $item  الى المحل $shop بتاريخ $date";

                        $check_debit_log->update([
                            'qty' => $stock,
                            'amount' => $wholesalePrice * $stock,
                            'note' => $debit_note,
                        ]);

                    } else {

                        $debit_note = "قام المستودع بإرسال $stock من المنتج $item  الى المحل $shop بتاريخ $date";

                        DebitLog::query()->create([
                            'merchant_debit_id' => $merchantDebit->id,
                            'user_product_id' => $userProduct->id,
                            'product_color_id' => $productColorId,
                            'merchant_id' => $request->get('merchant'),
                            'request_date' => $now,
                            'shop_id' => $shop_id,
                            'qty' => $stock,
                            'amount' => $wholesalePrice * $stock,
                            'note' => $debit_note,
                            'type' => 'store'
                        ]);

                    }
                }
            }

            $shopAccount = $userProduct->user;
            $user = auth()->user();
            $admin = User::query()->where('role_id', User::ROLE_ADMIN)->first();

            // NotificationJob::dispatch($shopAccount, $userProduct, $note, $user)->onQueue('notify');
            // NotificationJob::dispatch($admin, $userProduct, $note, $user)->onQueue('notify');
            // NotificationJob::dispatch($user, $userProduct, $note, $user)->onQueue('notify');

            if($shopAccount && $shopAccount->id != $user->id) {
                $shopAccount->notify(new ShopNotification($userProduct, $note, $user,$logsArr));
            }

            $admin->notify(new ShopNotification($userProduct, $note, $user,$logsArr));
            $user->notify(new ShopNotification($userProduct, $note, $user,$logsArr));





        }


        $this->clearHomeCache();
        return "success";
                // if($request->has('travel')){
                //     $userId = $request->get('user');
                //     $productColorId = $request->get('product');
                // }else{
                //     $userId = $request->get('user')['id'];
                //     $productColorId = isset($request->get('product')['product_color_id'])? $request->get('product')['product_color_id']: $request->get('product')['id'];
                // }
                // if($request->get('stock') > 0){
                //     // $userId = $request->get('user');
                //     // $productColorId = $request->get('product');
                //     $stock = $request->get('stock');
                //     $size = $request->get('size');
                //     $user = User::find($userId);
                //     $exists = UserProduct::query()
                //         ->where('user_id', $userId)
                //         ->where('product_color_id', $productColorId)
                //         ->where('size' ,$size )->first();
                //     $productColor = ProductColor::where('sizes','LIKE','%'.$request->get('barcode').'%')->first();
                //     $size_exist = false;
                //     $stock_available = false;
                //     if(!is_null($productColor->sizes)){
                //         $sizes = json_decode($productColor->sizes,true);
                //         if(!is_null($sizes)){
                //             if(count($sizes) > 0){
                //                 $available_sizes = array_column($sizes, 'size');
                //                 $size_exist = in_array($size,$available_sizes);
                //                 if($size_exist){
                //                     $index = array_search($size, $available_sizes);
                //                     if($stock <= $sizes[$index]['stock']){
                //                         $sizes[$index]['stock'] = $sizes[$index]['stock'] - $stock;
                //                         $productColor->sizes = json_encode($sizes);
                //                         $stock_available = true;
                //                     }
                //                 }
                //             }
                //         }
                //     }
                //     if(!$size_exist) return "nosize";
                //     if(!$stock_available) return "nostock";
                //     if ($request->has('travel'))
                //     {
                //         if ($request->has('is_warehouse')){
                //             if (auth()->user()->role_id === User::ROLE_WAREHOUSE){
                //                 $myItem = UserProduct::query()->where('user_id', auth()->id())
                //                     ->where('product_color_id', $productColorId)
                //                     ->where('size' ,$size )->first();
                //                 $myItemStock = $myItem->stock;
                //                 $myItem->update(['stock' => $myItemStock - $stock]);
                //             }
                //         }
                //     }else{
                //         if (auth()->user()->role_id === User::ROLE_WAREHOUSE){
                //             $myItem = UserProduct::query()->where('user_id', auth()->id())
                //                 ->where('product_color_id', $productColorId)
                //                 ->where('size' ,$size )->first();
                //             $myItemStock = $myItem->stock;
                //             $myItem->update(['stock' => $myItemStock - $stock]);
                //         }
                //     }
                //     if ($exists){
                //         $copy = clone $exists;
                //         $oldStock = $copy->stock;
                //     }else{
                //         $oldStock = 0;
                //     }

                //     $retailPrice = $request->get('retail_price');
                //     $wholesalePrice = $request->get('wholesale_price');
                //     $userProduct = UserProduct::query()->updateOrCreate([
                //         'user_id' => $userId,
                //         'product_color_id' => $productColorId,
                //         'size' => $size,
                //         'country_id' => $user->country_id,
                //         'barcode' => $request->get('barcode')
                //     ],[
                //         'user_id' => $userId,
                //         'product_color_id' => $productColorId,
                //         'size' => $size,
                //         'country_id' => $user->country_id,
                //         'barcode' => $request->get('barcode'),
                //         'stock' => $oldStock + $stock,
                //         'retail_price' => $retailPrice,
                //         'wholesale_price' => $wholesalePrice,
                //         'merchant_id' => $request->get('merchant')
                //     ]);
                //     Date::setLocale('ar');
                //     $date = Date::parse($userProduct->updated_at)->timezone('Asia/Beirut')->format('d-m-Y h:i a');
                //     $shop = $userProduct->user->name;
                //     $item = $userProduct->productColor->product_name;
                //     $note = "قام المستودع بإرسال $stock من المنتج $item إلى المحل $shop بتاريخ $date ";
                //     $note .= "بسعر $wholesalePrice للمحل و $retailPrice للتجزئة ";
                //     $log = UserProductLog::query()->create([
                //         'user_product_id' => $userProduct->id,
                //         'note' => $note
                //     ]);
                //     if ($creditor = $request->get('merchant')){
                //         $amount = $wholesalePrice * $stock;
                //         Debit::query()->create([
                //             'creditor_id' => $creditor,
                //             'debtor_id' => $userId,
                //             'amount' => $amount,
                //             'user_product_id' => $userProduct->id,
                //             'user_product_log_id' => $log->id
                //         ]);

                //         $userProduct->user->wallet->update(['debit' => DB::raw("debit + $amount")]);
                //         User::query()->find($creditor)->wallet->update(['credit' => DB::raw("credit + $amount")]);

                //         $merchantDebit = MerchantDebit::query()->firstOrCreate([
                //             'creditor_id' => $creditor,
                //             'debtor_id' => $userProduct->user_id
                //         ],[
                //             'creditor_id' => $creditor,
                //             'debtor_id' => $userProduct->user_id,
                //             'amount' => $amount
                //         ]);

                //         if (!$merchantDebit->wasRecentlyCreated)
                //             $merchantDebit->update(['amount' => DB::raw("amount + $amount")]);

                //         $note = "قام المستودع بإرسال $stock من المنتج $item إلى المحل $shop بتاريخ $date ";
                //         $note .= "بسعر $wholesalePrice";
                //         DebitLog::query()->create([
                //             'merchant_debit_id' => $merchantDebit->id,
                //             'user_product_id' => $userProduct->id,
                //             'amount' => $amount,
                //             'note' => $note
                //         ]);
                //     }
                //     $shopAccount = $userProduct->user;
                //     $user = auth()->user();
                //     $admin = User::query()->where('role_id', User::ROLE_ADMIN)->first();
                //     NotificationJob::dispatch($shopAccount, $userProduct, $note, $user)->onQueue('notify');
                //     NotificationJob::dispatch($admin, $userProduct, $note, $user)->onQueue('notify');
                // }
        return null;
    }

    public function update(Request $request, UserProduct $userProduct)
    {
        $userProduct->update($request->all());
        $this->clearHomeCache();
    }

    public function getUserProducts(Request $request, $all = false)
    {
        $user = auth()->user();

        $userProducts = UserProduct::query()
            ->with(['productColor', 'productColor.product', 'user'])
            ->where('stock', '>', 0)
            ->where('country_id',$user->country_id)
            ->whereNotNull('size');

        if ($shopId = $request->get('shop')) {
            $userProducts->where('user_id', $shopId);
        } else {
            if ($user->role_id != User::ROLE_ADMIN)
                if (!$all)
                    $userProducts->where('user_id', $user->id);
        }

        if ($search = $request->get('search')){
            $userProducts->where('barcode', 'LIKE', "%$search%");
        }
        $country = $user->country_id;
        $userProducts->whereHas('user', function ($query) use ($country){
            $query->where('country_id',$country);
        });
        if ($userId = $request->get('user'))
            $userProducts->where('user_id', $userId);

        if ($productId = $request->get('product'))
            $userProducts->where('product_id', $productId);

        if($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');
            $sortableArray = app(UserProduct::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $userProducts->orderBy($field, $direction);
            }else{
                $userProducts->orderByDesc('id');
            }
        }else{
            $userProducts->orderByDesc('id');
        }
        return $userProducts->get();
    }

    public function getUserProductsAll(Request $request, $all = false)
    {
        $user = auth()->user();

        $userProducts = UserProduct::query()
            ->with(['productColor', 'productColor.product', 'user'])
            ->where('stock', '>', 0)
            ->where('country_id',$user->country_id)
            ->whereNotNull('size');

        if ($shopId = $request->get('shop')) {
            $userProducts->where('user_id', $shopId);
        } else {
            if ($user->role_id != User::ROLE_ADMIN)
                if (!$all)
                    $userProducts->where('user_id', $user->id);
        }

        if ($request->get('search') || $request->params != null){
            // $userProducts->whereHas('productColor', function ($query) use ($search){
            //     $query->whereHas('product', function ($q) use ($search){
            //         $q->where('name', 'LIKE', "%$search%");
            //     })->orWhere('barcode', 'LIKE', "%$search%");
            // })->orWhere('barcode', 'LIKE', "%$search%");;
            if($request->get('search') != null) {
                //$userProducts->where('barcode', 'LIKE', "%$request->get('search')%");
                $userProducts->where('barcode', 'LIKE', '%' . $request->get('search') . '%');
            } else {
                $searchArr = json_decode($request->params,true);
                if(! empty($searchArr) && array_key_exists('search',$searchArr)) {
                    //$userProducts->where('barcode', 'LIKE', " % " . $searchArr['search'] . " % ");
                    $userProducts->where('barcode', 'LIKE', '%' . $searchArr['search'] . '%');
                }
            }
        }
        $country = $user->country_id;
        // $userProducts->whereHas('productColor', function ($query) use ($country){
        //     $query->where('country_id',$country);
        // });
        $userProducts->whereHas('user', function ($query) use ($country){
            $query->where('country_id',$country);
        });
        if ($userId = $request->get('user'))
            $userProducts->where('user_id', $userId);

        if ($shopId = $request->get('shop'))
            $userProducts->where('user_id', $shopId);

        if ($productId = $request->get('product'))
            $userProducts->where('product_id', $productId);

        if($request->has(['field', 'direction'])){
            $field = $request->get('field');
            $direction = $request->get('direction');
            $sortableArray = app(UserProduct::class)->getFillable();
            $sortableArray[] = 'id';
            if(in_array($field, $sortableArray)){
                $userProducts->orderBy($field, $direction);
            }else{
                $userProducts->orderByDesc('product_color_id');
            }
        }else{
            $userProducts->orderByDesc('product_color_id');
        }

        return $userProducts->paginate(10)->appends(request()->query());
    }

}
