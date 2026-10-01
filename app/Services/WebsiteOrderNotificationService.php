<?php

namespace App\Services;

use App\Models\User;
use App\Models\WebsiteOrder;
use App\Notifications\OrderNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebsiteOrderNotificationService
{
    public function deliver(WebsiteOrder $order): void
    {
        if ($order->notifications_sent_at) {
            return;
        }
        $users = User::query()->whereIn('role_id', [User::ROLE_ADMIN, User::ROLE_WAREHOUSE, User::ROLE_SHOP])
            ->where(function ($query) use ($order) {
                $query->where('role_id', User::ROLE_ADMIN)->orWhere('country_id', $order->country_id);
            })->get();

        foreach ($users as $user) {
            // A deterministic primary key also prevents duplicates on concurrent retries.
            $hex = md5('website-order:' . $order->id . ':user:' . $user->id);
            $id = substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4)
                . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
            $notification = new OrderNotification($order, __('New Order') . ' #' . $order->barcode, $order->buyer);
            $notification->id = $id;
            $inserted = DB::table('notifications')->insertOrIgnore([
                'id' => $id, 'type' => OrderNotification::class,
                'notifiable_type' => $user->getMorphClass(), 'notifiable_id' => $user->id,
                'data' => json_encode($notification->toArray($user), JSON_THROW_ON_ERROR),
                'read_at' => null, 'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($inserted) {
                try {
                    $user->notifyNow($notification, ['broadcast']);
                } catch (\Throwable $exception) {
                    // Persisted notifications remain available to polling if broadcasting fails.
                    Log::warning('Website order broadcast unavailable.', ['order_id' => $order->id]);
                }
            }
        }
        $order->forceFill(['notifications_sent_at' => now()])->saveQuietly();
    }

    public function snapshot(User $user): array
    {
        $query = $user->notifications()->where(function ($query) {
            $query->where('data->source', 'website')
                ->orWhere(function ($legacy) {
                    $legacy->whereNull('data->source')->whereNotNull('data->table->barcode')
                        ->whereNull('data->table->product_color');
                });
        });
        if ((int) $user->role_id !== User::ROLE_ADMIN) {
            $query->where(function ($country) use ($user) {
                $country->where('data->table->country_id', (int) $user->country_id)
                    ->orWhereNull('data->table->country_id');
            });
        }
        $ids = (clone $query)->whereNull('read_at')->pluck('id')->all();
        return [
            'website_order_count' => count($ids),
            'website_order_ids' => $ids,
            'notifications_count' => $user->unreadNotifications()->count(),
            'notifications' => $user->notifications()->latest()->limit(5)->get(),
        ];
    }

    public function sendAdminEmails(WebsiteOrder $order): void
    {
        $rows = DB::table('notifications')->where('type', OrderNotification::class)
            ->where('data->source', 'website')->where('data->table->id', $order->id)->get();
        foreach ($rows as $row) {
            $data = json_decode($row->data, true);
            if (!empty($data['admin_email_sent_at'])) {
                continue;
            }
            $user = User::find($row->notifiable_id);
            if (!$user) {
                continue;
            }
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\NewOrderAdminEmail($order));
            $data['admin_email_sent_at'] = now()->toIso8601String();
            DB::table('notifications')->where('id', $row->id)->update(['data' => json_encode($data)]);
        }
    }
}
