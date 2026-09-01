<?php

use Illuminate\Support\Facades\Storage;
use Spatie\ImageOptimizer\OptimizerChainFactory;


function uploadImage($key = 'avatar', $folder = 'users', $oldFile = false)
{
    $request = request();
    if ($request->hasFile($key)) {

        if ($oldFile)
            Storage::disk('public')->delete($oldFile);

        $file = $request->file($key);
        $format = $file->getClientOriginalExtension();

        $name = time() .generateRandomString(3). ".$format";
        Storage::put($name, $file->getContent());
        if (Storage::move($name, "public/$folder/" . $name)) {
            $optimizerChain = OptimizerChainFactory::create();
            $optimizerChain->optimize(storage_path('app/public/' . $folder . '/' . $name));
            return "/$folder/" . $name;
        }
    }
    return false;
}

function updateUploadImage($item, $key = 'avatar', $folder = 'users')
{
    $request = request();
    if ($request->hasFile($key)){
        if ($item->{$key} != null){
            Storage::disk('public')->delete($item->{$key});
        }

        $request = request();
        $file = $request->file($key);
        $format = $file->getClientOriginalExtension();

        $name = time(). generateRandomString(4) . ".$format";
        Storage::put($name, $file->getContent());
        if (Storage::move($name, "public/$folder/" . $name)) {
            $optimizerChain = OptimizerChainFactory::create();
            $optimizerChain->optimize(storage_path('app/public/' . $folder . '/' . $name));
            return "/$folder/" . $name;
        }
    }

    return false;
}

function deleteMedia($item, $key = 'avatar')
{
    if ($item->{$key} != null){
        Storage::disk('public')->delete($item->{$key});
    }
}

function deleteMultipleMedia($item, $key = 'images')
{
    foreach ($item->{$key} as $image){
        Storage::disk('public')->delete($image);
    }
}

function uploadFile($key = "file", $folder = 'projects', $oldFile = false)
{
    $request = request();
    if ($request->hasFile($key)) {
        if ($oldFile)
            Storage::disk('public')->delete($oldFile);

        $uploadedFile = $request->file($key);
        $moved = Storage::disk('public')->put($folder, $uploadedFile);

        if ($moved)
            return $moved; // url to file
    }
    return $oldFile;
}


function uploadMultiImages($key = "photos", $folder = 'projects', $oldFiles = false)
{
    if(request()->hasFile($key)){
        if ($oldFiles) {
            foreach ($oldFiles as $file)
                Storage::disk('public')->delete($file);
        }

        $request = request();
        $imagesNames = array();
        foreach ($request->file($key) as $image) {
            Storage::disk('public')->exists($folder) or Storage::disk('public')->makeDirectory($folder);
            $imageName = Storage::disk('public')->put($folder, $image);
            $imagesNames[] = $imageName;
        }
        return $imagesNames;
    }

    return $oldFiles;
}
