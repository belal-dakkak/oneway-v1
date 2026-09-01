<?php

namespace App\Jobs;

use App\Notifications\OrderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotificationOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $shop, $user, $order, $note = null;

    /**
     * Create a new job instance
     * @return void
     */
    public function __construct($shop, $user, $order, $note)
    {
        $this->note = $note;
        $this->user = $user;
        $this->shop = $shop;
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Log::info('NotificationOrderJob processing', [
            'shop_id' => $this->shop->id,
            'shop_email' => $this->shop->email,
            'order_id' => $this->order->id,
            'note' => $this->note
        ]);

        try {
            $this->shop->notify(new OrderNotification($this->order, $this->note, $this->user));
            \Log::info('Notification sent successfully', ['shop_id' => $this->shop->id]);
        } catch (\Exception $e) {
            \Log::error('Failed to send notification', [
                'shop_id' => $this->shop->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        \Log::error('NotificationOrderJob failed', [
            'shop_id' => $this->shop->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
