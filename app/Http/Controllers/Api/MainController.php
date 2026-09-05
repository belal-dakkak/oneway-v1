<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\ContactRequest;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Color;
use App\Models\Contact;
use App\Models\Product;
use App\Services\CurrencyService;
use App\Support\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;


class MainController extends ApiController
{
    public function currencies(Request $request): JsonResponse
    {
        $countryId = Country::idForCurrency(
            $request->header('Accept-Currency', 'AED'),
            $request->header('Accept-Country')
        );

        if ($countryId === Country::SYRIA) {
            $display = app(CurrencyService::class)->displayForCountry($countryId);
            return $this->respondSuccess([
                [
                    'code' => 'USD',
                    'symbol' => '$',
                    'base' => true,
                    'transaction_enabled' => true,
                    'display_only' => false,
                ],
                [
                    'code' => 'SYP',
                    'symbol' => 'ل.س',
                    'base' => false,
                    'transaction_enabled' => false,
                    'display_only' => true,
                    'rate' => $display['rate'] ?? null,
                    'approximate' => true,
                    'available' => $display !== null,
                ],
            ]);
        }

        $currencies = [
            [
                'code' => 'USD',
                'symbol' => '$'
            ],
            [
                'code' => 'LBP',
                'symbol' => 'ل.ل'
            ],
            [
                'code' => 'AED',
                'symbol' => 'د.إ'
            ],
            [
                'code' => 'SYP',
                'symbol' => 'ل.س'
            ]
        ];

        return $this->respondSuccess($currencies);
    }

    public function branches(Request $request): JsonResponse
    {
        $lang = $request->header('Accept-Language', 'en');

        $branches = Branch::query()->get();
        $branchesArray = [];
        foreach ($branches as $branch) {
            if ($lang === 'en') {
                $branch->name = $branch->name_en;
                $branch->address = $branch->address_en;
            }else {
                $branch->name = $branch->name_ar;
                $branch->address = $branch->address_ar;
            }
            $branchesArray[] = $branch;
        }

        return $this->respondSuccess($branchesArray);
    }

    public function attributes(Request $request): JsonResponse
    {
        $currency = $request->header('Accept-Language', 'en');
        if ($currency == 'en')
            $lang  = 'en';
        else
            $lang = 'ar';

        $categories = Category::query()->get();
        $allCategories = [];
        foreach ($categories as $category){
            if ($lang == 'en'){
                $category->name = $category->name_en;
            }
            $allCategories[] = $category;
        }
        $colors = Color::query()->get();
        $allColors = [];
        foreach ($colors as $color){
            if ($lang == 'en'){
                $color->name = $color->name_en;
            }
            $allColors[] = $color;
        }

        $minPrice =  1;
        $maxPrice = ceil(Product::query()->max('retail_price'));

        return $this->respondSuccess([
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'colors' => $allColors,
            'categories' => $allCategories
        ]);

    }

    public function about(): JsonResponse
    {
        if (Session::get('lang') == 'ar'){
            $about = "One Way is a wholesale clothing manufacturer and store. We offer a unique variety of clothes categories ranging from womenswear and menswear to childrenswear with quality designing services. We are a trusted clothing wholesale supplier, conveniently located in Lebanon and UAE with exportation to various countries around the globe.";
            $about .= " At One Way, we take great pride in providing at-scale garments that last for years, and by doing so, we help stores around the world achieve their trading goals and enhance their brand image.";
        }
        else{
            $about = " وان واي هي شركة تصنيع وتوريد ملابس بالجملة، تقدم مجموعة متنوعة وفريدة من الملابس النسائية وملابس الأطفال مع خدمات تصميم عالية الجودة باعتبارها مورود جملة موثوق بمكان استراتيجي في لبنان و الإمارات العربية المتحدة مع إمكانية التصدير إلى مختلف البلدان.";
            $about .= " تفتخر وان واي بتقديم ملابس ذات جودة عالية على نطاق واسع حيث نساعد المتاجر في جميع أنحاء العالم على تحقيق أهدافهم المالية وتمكين علامتهم التجارية.";
        }

        return $this->respondSuccess($about);
    }

    public function contact(ContactRequest $request): JsonResponse
    {
        Contact::query()->create($request->all());
        return $this->respondSuccess('sent successfully');
    }
}
