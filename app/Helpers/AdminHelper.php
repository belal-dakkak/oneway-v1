<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function newStd($array = []): stdClass
{
    $std = new \stdClass();
    foreach ($array as $key => $value) {
        $std->$key = $value;
    }
    return $std;
}

function getSizes($sizes)
{
    $sizesArray = [];
    foreach ($sizes as $size){
        $sizesArray[] = newStd(['name' => $size, 'value'=> $size, 'id'=> $size]);
    }
    return $sizesArray;
}

function getColors($colors)
{
    $colorsArray = [];
    foreach ($colors as $color){
        $sizes = json_decode($color->sizes,true);
        if(is_null($sizes) || is_null($color->sizes))
            $sizes = [['stock' => 0,'size' => 'S']];
        $nsizes = array();
        foreach ($sizes as $size) {
            array_push($nsizes,['stock' => $size['stock'], 'size' => newStd(['name' => $size['size'], 'value'=> $size['size'], 'id'=> $size['size']])]);
        }
        $colorsArray[] = newStd(['image' => $color->image, 'stock'=> $color->stock, 'color'=> $color->color, 'id' => $color->id, 'image_url' => $color->photo_url, 'barcode' => $color->barcode,'sizes' => $nsizes]);
    }
    return $colorsArray;
}

function getNewSizesVariables(): array
{
    $sizes = [];
    $sizes[] = newStd(['name' => 'XS', 'value'=> 'XS', 'id'=> 'XS']);
    $sizes[] = newStd(['name' => 'S', 'value'=> 'S', 'id'=> 'S']);
    $sizes[] = newStd(['name' => 'M', 'value'=> 'M', 'id'=> 'M']);
    $sizes[] = newStd(['name' => 'L', 'value'=> 'L', 'id'=> 'L']);
    $sizes[] = newStd(['name' => 'XL', 'value'=> 'XL', 'id'=> 'XL']);
    $sizes[] = newStd(['name' => 'XXL', 'value'=> 'XXL', 'id'=> 'XXL']);
    $sizes[] = newStd(['name' => '3XL', 'value'=> '3XL', 'id'=> '3XL']);
    $sizes[] = newStd(['name' => '4XL', 'value'=> '4XL', 'id'=> '4XL']);
    $sizes[] = newStd(['name' => '5XL', 'value'=> '5XL', 'id'=> '5XL']);
    $sizes[] = newStd(['name' => '6XL', 'value'=> '6XL', 'id'=> '6XL']);
    $sizes[] = newStd(['name' => '7XL', 'value'=> '7XL', 'id'=> '7XL']);
    $sizes[] = newStd(['name' => '8XL', 'value'=> '8XL', 'id'=> '8XL']);
    $sizes[] = newStd(['name' => 'FreeSize', 'value'=> 'FreeSize', 'id'=> 'FreeSize']);
    for ($i=36; $i <= 58; $i += 2) {
        $sizes[] = newStd(['name' => "$i", 'value'=> "$i", 'id'=> "$i"]);
    }
    return $sizes;
}

function getSizesVariables(): array
{
    $sizes = [];
    $sizes[] = newStd(['name' => 'S M L XL', 'value'=> 'S M L XL', 'id'=> 'S M L XL']);
    $sizes[] = newStd(['name' => 'M L XL XXL', 'value'=> 'M L XL XXL', 'id'=> 'M L XL XXL']);
    $sizes[] = newStd(['name' => '1', 'value'=> '1', 'id'=> '1']);
    $sizes[] = newStd(['name' => '2', 'value'=> '2', 'id'=> '2']);
    $sizes[] = newStd(['name' => '3', 'value'=> '3', 'id'=> '3']);
    $sizes[] = newStd(['name' => '4', 'value'=> '4', 'id'=> '4']);
    $sizes[] = newStd(['name' => '5', 'value'=> '5', 'id'=> '5']);
    $sizes[] = newStd(['name' => '6', 'value'=> '6', 'id'=> '6']);
    $sizes[] = newStd(['name' => '7', 'value'=> '7', 'id'=> '7']);
    $sizes[] = newStd(['name' => '8', 'value'=> '8', 'id'=> '8']);
    $sizes[] = newStd(['name' => '9', 'value'=> '9', 'id'=> '9']);
    $sizes[] = newStd(['name' => '10', 'value'=> '10', 'id'=> '10']);
    $sizes[] = newStd(['name' => '12', 'value'=> '12', 'id'=> '12']);
    $sizes[] = newStd(['name' => '13', 'value'=> '13', 'id'=> '13']);
    $sizes[] = newStd(['name' => '14', 'value'=> '14', 'id'=> '14']);
    $sizes[] = newStd(['name' => '15', 'value'=> '15', 'id'=> '15']);
    $sizes[] = newStd(['name' => '16', 'value'=> '16', 'id'=> '16']);
    $sizes[] = newStd(['name' => '17', 'value'=> '17', 'id'=> '17']);
    $sizes[] = newStd(['name' => '18', 'value'=> '18', 'id'=> '18']);
    $sizes[] = newStd(['name' => '36', 'value'=> '36', 'id'=> '36']);
    $sizes[] = newStd(['name' => '38', 'value'=> '38', 'id'=> '38']);
    $sizes[] = newStd(['name' => '40', 'value'=> '40', 'id'=> '40']);
    $sizes[] = newStd(['name' => '42', 'value'=> '42', 'id'=> '42']);
    $sizes[] = newStd(['name' => '44', 'value'=> '44', 'id'=> '44']);
    $sizes[] = newStd(['name' => '46', 'value'=> '46', 'id'=> '46']);
    $sizes[] = newStd(['name' => '48', 'value'=> '48', 'id'=> '48']);
    $sizes[] = newStd(['name' => '50', 'value'=> '50', 'id'=> '50']);
    $sizes[] = newStd(['name' => '52', 'value'=> '52', 'id'=> '52']);
    $sizes[] = newStd(['name' => '54', 'value'=> '54', 'id'=> '54']);
    $sizes[] = newStd(['name' => '56', 'value'=> '56', 'id'=> '56']);
    $sizes[] = newStd(['name' => '58', 'value'=> '58', 'id'=> '58']);
    $sizes[] = newStd(['name' => 'S', 'value'=> 'S', 'id'=> 'S']);
    $sizes[] = newStd(['name' => 'XS', 'value'=> 'XS', 'id'=> 'XS']);
    $sizes[] = newStd(['name' => 'M', 'value'=> 'M', 'id'=> 'M']);
    $sizes[] = newStd(['name' => 'L', 'value'=> 'L', 'id'=> 'L']);
    $sizes[] = newStd(['name' => 'XL', 'value'=> 'XL', 'id'=> 'XL']);
    $sizes[] = newStd(['name' => 'XXL', 'value'=> 'XXL', 'id'=> 'XX']);
    $sizes[] = newStd(['name' => '3XL', 'value'=> '3XL', 'id'=> '3X']);
    $sizes[] = newStd(['name' => '4XL', 'value'=> '4XL', 'id'=> '4X']);
    $sizes[] = newStd(['name' => '5XL', 'value'=> '5XL', 'id'=> '5X']);
    $sizes[] = newStd(['name' => '6XL', 'value'=> '6XL', 'id'=> '6X']);
    $sizes[] = newStd(['name' => '7XL', 'value'=> '7XL', 'id'=> '7X']);
    $sizes[] = newStd(['name' => '8XL', 'value'=> '8XL', 'id'=> '8X']);
    return $sizes;
}

function getAvailableCountries(): array
{
    $countries = [];
    $countries[] = newStd(['name' => 'Lebanon', 'value'=> 'Lebanon', 'id'=> 'Lebanon']);
    $countries[] = newStd(['name' => 'UnitedArabEmirates', 'value'=> 'UnitedArabEmirates', 'id'=> 'UnitedArabEmirates']);
    return $countries;
}

function storageImage($file, $default = '')
{
    if (Str::contains($file, 'http'))
        return $file;
    if (!empty($file)) {
        return str_replace('\\', '/', Storage::disk('public')->url($file));
    }

    return $default;
}

function translations($json)
{
    if(!file_exists($json)) {
    return [];
    }
    return json_decode(file_get_contents($json), true);
}
