<?php

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
// STRING HELPERS
function generateRandomString($length = 7, $capital = false): string
{
    if ($capital)
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    else
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[mt_rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function convertSizeToNumber($size)
{
    $sizes = array('XS','S','M','L','XL','XXL','3XL','4XL','5XL','6XL','7XL','8XL','FreeSize');
    if(!in_array($size,$sizes)) return $size;
    $index = array_search($size, $sizes);
    return $index+59;
}
function convertNumberToSize($size)
{
    if($size <= 58) return $size;
    $nsizes = array();
    for ($i=59; $i < 71; $i++) {
        array_push($nsizes,$i);
    }
    $sizes = array('XS','S','M','L','XL','XXL','3XL','4XL','5XL','6XL','7XL','8XL','FreeSize');
    $index = array_search($size, $nsizes);
    return $sizes[$index];
}

function generateRandomNumber($length = 7): string
{
    $characters = '0123456789';

    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[mt_rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function plural($string): string
{
    return Str::plural($string);
}

function uFirst($string = ''): string
{
    return 'App\Models\Attributes\\'.Str::of($string)->camel()->ucfirst();
}

function modelName($string = ''): string
{
    return 'App\Models\\'.Str::of($string)->camel()->ucfirst();
}

function camel($string = ''): string
{
    return Str::camel($string);
}

function raw($string = ''): Stringable
{
    return Str::of($string)->replaceMatches('/\d+/u','');
}

function toTitle($string = ''): Stringable
{
    return Str::of($string)->replace('_', ' ')->replaceMatches('/\d+/u',' ')->ucfirst();
}

function toResource($string = ''): Stringable
{
    return Str::of($string)->replace('_', ' ')->ucfirst()->replaceMatches(' ','');
}

function toDesc($string = '')
{
    if (strlen($string) > 70)
        return Str::of($string)->substr(0, strpos($string, ' ', 70))->append(' ...');
    return $string;
}

function toDescEditor($string = '')
{
    if (strlen($string) > 70)
        return Str::of($string)->substr(0, strpos($string, '>', 70))->append(' ...');
    return $string;
}

function getFirstName($name): string
{
    return explode(" ", $name)[0] ?? "";
}

function contains($string, $token): bool
{
    return Str::contains($string, $token);
}

function getAction(): ?string
{
    return explode("@", Route::currentRouteAction())[1] ?? "";
}

function getActionMethod(): ?string
{
    $currentAction = explode("@", Route::currentRouteAction())[1];
    if ($currentAction == 'create')
        return 'POST';
    else
        return 'PUT';
}

function getNextAction($item, $entity = null): string
{
    $currentAction = explode("@", Route::currentRouteAction())[1];
    if ($currentAction == 'create')
        return route('admin.'.plural($item).'.store');
    else
        return route('admin.'.plural($item).'.update', [strval($item) => $entity]);
}

// MODEL HELPERS
function getArrayOfModelId($parentRelations, $columnName): array
{
    $models = [];

    foreach ($parentRelations as $parentRelation)
        array_push($models,$parentRelation->$columnName()->first()->id);

    return $models;
}

function getArrayOfModel($parentRelations, $columnName): array
{
    $models = [];

    foreach ($parentRelations as $parentRelation)
        array_push($models,$parentRelation->$columnName()->first());

    return $models;
}

function getItemInArrayByColumn($search, $array, $columnName){
    if (!isset($search))
        return null;

    $index = array_search($search, array_column($array, $columnName));

    return $array[$index];
}

function getVisibleAttributes($model): array
{
    $model = app($model);
    return $model->visibleAttributes ?? [];
}

function getMediaAttributes($model): array
{
    $model = app($model);
    return $model->mediaAttributes ?? [];
}

function getTranslatableAttributes($model): array
{
    $model = app($model);
    return $model->translatedAttributes;
}

function label($entity, $attribute, $type)
{
    switch ($type) {
        case 'text':
            return $entity->$attribute;
        case 'multi_statuses':
            return $entity->$attribute->name;
        case 'relation':
            if ($entity->$attribute)
                return $entity->$attribute->name ?? $entity->$attribute->title;
            return __('admin.all_users');
        case 'status':
            return  $entity->{$attribute} ? __('admin.yes') : __('admin.no');
    }
}

function populateModelData(Request $request, $model): array
{
    $model = app($model);
    $data = [];
    if ($model->translatedAttributes){
        foreach (config('translatable.locales') as $locale){
            foreach ($model->translatedAttributes as $attribute){
                if ($request->get($attribute.':' . $locale) != null)
                    $data[$locale][$attribute] = $request->input($attribute.':'. $locale);
            }
        }
        foreach ($model->translatedAttributes as $attribute){
            if ($request->get($attribute) != null)
                $data['en'][$attribute] = $request->input($attribute);
        }
    }


    foreach ($model->getFillable() as $item){
        if ($request->get($item) != null){
            $data[$item] = $request->input($item);
        }
    }


    return $data;
}

function transformDataForVue($items)
{
    return $items->transform(function ($item) {
        return $item->toArray();
    });
}


// Removed transformProductsForVue as products are now grouping correctly by Product ID

function transformDataForVueCharts($items)
{
    $data = [];
    foreach ($items as $item){
        $data[$item->country] = $item->count;
    }
    return $data;
}

function transformDataForVueSelect($items)
{
    return $items->transform(function($item){
        $item = $item->toArray();
        $item['value'] = $item['id'];
        return $item;
    });
}

 /**
 * The attributes that are mass assignable.
 *
 * @var array
 */
function paginate($items, $perPage = 5, $page = null, $options = [])
{
    $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
    $items = $items instanceof Collection ? $items : Collection::make($items);
    return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
}

function transformItemForVue($item, $model)
{
    if (!$item)
        return [];

    $transformedItem = $item->toArray();
    // $data = array();
    // if($model == Setting::class){
    //     foreach ($item as $key => $setting) {

    //     }
    // }
    $model = app($model);
    if ($model->translatedAttributes) {
        foreach (config('translatable.locales') as $locale){
            foreach ($model->translatedAttributes as $attribute){
                if ($item->translate($locale))
                    $transformedItem[$attribute.'_'.$locale] = $item->translate($locale)->{$attribute};
            }
        }
    }
    if ($model->appends){
        foreach ($model->appends as $append){
            $transformedItem[$append] = $item->{$append};
        }
    }
    return $transformedItem;
}

function thousandsCurrencyFormat($num) {

    if($num>1000) {

        $x = round($num);
        $x_number_format = number_format($x);
        $x_array = explode(',', $x_number_format);
        $x_parts = array('k', 'm', 'b', 't');
        $x_count_parts = count($x_array) - 1;
        $x_display = $x;
        $x_display = $x_array[0] . ((int) $x_array[1][0] !== 0 ? '.' . $x_array[1][0] : '');
        $x_display .= $x_parts[$x_count_parts - 1];

        return $x_display;

    }

    return $num;
}

function currencyExchange($value, $rate, $isInt = false, $forceRound = false)
{
    $usd = newStd([
        'rate'  => 1,
        'round' => false,
    ]);

    $aed = newStd([
        'round' => true,
    ]);

    $result = 0.00;

    $value = doubleval($value);

    if($rate > $usd->rate)
        $result = $aed->round || $forceRound ? round($value * $rate) : $value * $rate;
    else
        $result = $usd->round || $forceRound ? round($value * $rate) : $value * $rate;

    return $isInt ? $result : number_format($result, 2, '.', '');
}

