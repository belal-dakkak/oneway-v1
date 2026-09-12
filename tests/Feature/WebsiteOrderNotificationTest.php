<?php

namespace Tests\Feature;

use App\Jobs\DispatchWebsiteOrderNotifications;
use App\Mail\NewOrderAdminEmail;
use App\Mail\OrderConfirmationEmail;
use App\Models\User;
use App\Models\WebsiteOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebsiteOrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmation_is_marked_only_after_delivery_and_normal_retries_do_not_duplicate_it(): void
    {
        Mail::fake();
        Queue::fake();
        User::query()->create([
            'name' => 'Admin', 'email' => 'notify-admin@example.test', 'password' => 'secret',
            'role_id' => User::ROLE_ADMIN, 'country_id' => User::COUNTRY_SYRIA,
        ]);
        $order = WebsiteOrder::query()->create([
            'barcode' => 'WEB-NOTIFY-1', 'country_id' => User::COUNTRY_SYRIA,
            'first_name' => 'Customer', 'last_name' => 'Test', 'email' => 'customer@example.test',
            'payment_type' => 'cod', 'curr_type' => 'SYP', 'total_price' => 100000,
            'status' => WebsiteOrder::STATUS_PENDING,
        ]);

        $job = new DispatchWebsiteOrderNotifications($order->id);
        $job->handle();
        $job->handle();

        Mail::assertSent(OrderConfirmationEmail::class, 1);
        Mail::assertSent(NewOrderAdminEmail::class, 1);
        $this->assertNotNull($order->fresh()->confirmation_email_sent_at);
        $this->assertNotNull($order->fresh()->notifications_sent_at);
        $this->assertNull($order->fresh()->notification_last_error);
    }
}
