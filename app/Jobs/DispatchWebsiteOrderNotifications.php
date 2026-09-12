<?php

namespace App\Jobs;

use App\Models\WebsiteOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchWebsiteOrderNotifications implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900];
    private $orderId;

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
        $this->afterCommit = true;
    }

    public function handle(): void
    {
        $order = WebsiteOrder::query()->find($this->orderId);
        if ($order) {
            try {
                $order->sendNotificationsNow();
            } catch (\Throwable $exception) {
                $order->forceFill(['notification_last_error' => $exception->getMessage()])->save();
                throw $exception;
            }
        }
    }

    public function uniqueId(): string
    {
        return 'website-order-notifications:' . $this->orderId;
    }
}
