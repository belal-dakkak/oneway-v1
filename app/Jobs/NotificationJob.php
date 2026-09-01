<?php

namespace App\Jobs;

use App\Notifications\ShopNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $shopAccount, $userProduct, $note, $user = null;

    /**
     * Create a new job instance
     * @param $shopAccount
     * @param $userProduct
     * @param $note
     * @param null $user
     * @return void
     */
    public function __construct($shopAccount, $userProduct, $note, $user)
    {
        $this->shopAccount = $shopAccount;
        $this->userProduct = $userProduct;
        $this->note = $note;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->shopAccount->notify(new ShopNotification($this->userProduct, $this->note, $this->user));
    }
}
