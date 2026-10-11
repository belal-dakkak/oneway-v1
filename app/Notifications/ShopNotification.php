<?php

namespace App\Notifications;

use App\Models\UserProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ShopNotification extends Notification
{
    use Queueable;

    private $payload;

    public function __construct($table, string $message, $user, $logsArr = null)
    {
        $this->payload = $this->makePayload($table, $message, $user, $logsArr ?: []);
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        return $this->payload;
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->payload);
    }

    private function makePayload($table, string $message, $user, array $logs): array
    {
        if (!$table instanceof UserProduct) {
            return [
                'source' => 'inventory',
                'image_url' => null,
                'user' => $this->userSnapshot($user),
                'table' => is_array($table) ? $table : [],
                'logsArr' => $logs,
                'message' => $message,
            ];
        }

        $table->loadMissing(['productColor.product', 'productColor.color', 'user']);
        $productColor = $table->productColor;
        $product = $productColor ? $productColor->product : null;
        $color = $productColor ? $productColor->color : null;
        $imageUrl = $productColor ? $productColor->photo_url : null;

        return [
            'source' => 'inventory',
            'image_url' => $imageUrl,
            'user' => $this->userSnapshot($user),
            'table' => [
                'id' => (int) $table->id,
                'user_id' => (int) $table->user_id,
                'product_color_id' => (int) $table->product_color_id,
                'country_id' => (int) $table->country_id,
                'size' => (string) $table->size,
                'barcode' => (string) $table->barcode,
                'stock' => (int) $table->stock,
                'product_color' => $productColor ? [
                    'id' => (int) $productColor->id,
                    'product_id' => (int) $productColor->product_id,
                    'color_id' => (int) $productColor->color_id,
                    'barcode' => (string) $productColor->barcode,
                    'photo_url' => $imageUrl,
                    'product_name' => (string) $productColor->product_name,
                    'color_name' => $color ? (string) $color->name : '',
                    'product' => $product ? [
                        'id' => (int) $product->id,
                        'name' => (string) $product->name,
                        'name_en' => (string) $product->name_en,
                        'barcode' => (string) $product->barcode,
                        'details' => (string) $product->details,
                        'details_en' => (string) $product->details_en,
                    ] : null,
                    'color' => $color ? [
                        'id' => (int) $color->id,
                        'name' => (string) $color->name,
                        'name_en' => (string) $color->name_en,
                        'code' => (string) $color->code,
                    ] : null,
                ] : null,
            ],
            'logsArr' => array_values($logs),
            'message' => $message,
        ];
    }

    private function userSnapshot($user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'role_id' => (int) $user->role_id,
            'country_id' => (int) $user->country_id,
        ];
    }
}
