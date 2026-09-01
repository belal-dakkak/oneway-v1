<?php

namespace App\Mail;

use App\Models\WebsiteOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewOrderAdminEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $order;
    public $adminUrl;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(WebsiteOrder $order)
    {
        $this->order = $order;
        $this->adminUrl = config('app.url') . '/admin/orders/website';
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('New Order Received - #' . $this->order->barcode)
                    ->view('emails.new-order-admin');
    }
}
