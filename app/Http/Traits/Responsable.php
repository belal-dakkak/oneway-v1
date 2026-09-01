<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

trait Responsable
{
    public function respondSuccess($content = [], $paginator = []): JsonResponse
    {
        return response()->json([
            'result' => 'success',
            'content' => $content,
            'paginator' => $paginator,
            'error_des' => '',
            'error_code' => 0,
            'images_prefix' => Storage::disk('public')->url('')
        ]);
    }

    public function respondError($message, $validationError = null): JsonResponse
    {
        return response()->json([
            'result' => 'error',
            'content' => null,
            'error_des' => $message,
            'error_validation' => $validationError,
            'error_code' => 1,
            'date' =>date('Y-m-d')
        ],400);
    }

    public function respondOut($message): JsonResponse
    {
        return response()->json([
            'result' => 'error',
            'content' => [],
            'error_des' => $message,
            'error_code' => -1,
            'date' =>date('Y-m-d')
        ]);
    }

    public function respondMessage($message): JsonResponse
    {
        return response()->json([
            'result' => 'success',
            'content' => [],
            'error_des' => $message,
            'error_code' => 0,
            'date' =>date('Y-m-d')
        ]);
    }

    public function respondMobileError($message): JsonResponse
    {
        return response()->json([
            'result' => 'error',
            'content' => $message,
            'error_des' => $message,
            'error_code' => 1,
            'date' =>date('Y-m-d')
        ],200);
    }

}
