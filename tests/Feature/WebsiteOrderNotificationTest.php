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
    public function test_mail_failure_does_not_hide_or_duplicate_dashboard_notifications(): void
    {
        config(['broadcasting.default' => 'log']);
        $user = User::query()->create([
            'name' => 'Shop', 'email' => 'shop-notify@example.test', 'password' => 'secret',
            'role_id' => User::ROLE_SHOP, 'country_id' => User::COUNTRY_UAE,
        ]);
        $foreign = User::query()->create([
            'name' => 'Other', 'email' => 'other-notify@example.test', 'password' => 'secret',
            'role_id' => User::ROLE_SHOP, 'country_id' => User::COUNTRY_SYRIA,
        ]);
        $order = WebsiteOrder::query()->create([
            'barcode' => 'FAIL-MAIL', 'country_id' => User::COUNTRY_UAE,
            'email' => 'customer@example.test', 'payment_type' => 'cod', 'curr_type' => 'AED',
            'total_price' => 55, 'status' => WebsiteOrder::STATUS_PENDING,
        ]);
        Mail::shouldReceive('to')->twice()->andThrow(new \RuntimeException('SMTP unavailable'));
        for ($i = 0; $i < 2; $i++) {
            try { $order->sendNotificationsNow(); } catch (\RuntimeException $exception) {
                $this->assertSame('SMTP unavailable', $exception->getMessage());
            }
        }
        $this->assertSame(1, $user->unreadNotifications()->count());
        $this->assertSame(0, $foreign->notifications()->count());
        $this->assertNotNull($order->fresh()->notifications_sent_at);
        $this->assertNull($order->fresh()->confirmation_email_sent_at);
    }

    public function test_summary_counts_all_unread_orders_and_is_scoped_to_the_authenticated_user(): void
    {
        config(['broadcasting.default' => 'log']);
        $user = User::query()->create([
            'name' => 'Shop', 'email' => 'summary@example.test', 'password' => 'secret',
            'role_id' => User::ROLE_SHOP, 'country_id' => User::COUNTRY_UAE,
        ]);
        $service = app(\App\Services\WebsiteOrderNotificationService::class);
        for ($i = 0; $i < 8; $i++) {
            $order = WebsiteOrder::query()->create([
                'barcode' => 'SUMMARY-' . $i, 'country_id' => User::COUNTRY_UAE,
                'payment_type' => 'cod', 'curr_type' => 'AED', 'total_price' => 55,
            ]);
            $service->deliver($order);
            $order->update(['notifications_sent_at' => null]);
            $service->deliver($order); // deterministic ID protects even a retried marker
        }
        $this->actingAs($user)->getJson(route('notification.summary'))->assertOk()
            ->assertJsonPath('website_order_count', 8)->assertJsonCount(8, 'website_order_ids')
            ->assertJsonCount(5, 'notifications');
        $user->unreadNotifications()->first()->markAsRead();
        $this->getJson(route('notification.summary'))->assertJsonPath('website_order_count', 7);
        $user->update(['country_id' => User::COUNTRY_SYRIA]);
        $this->getJson(route('notification.summary'))->assertJsonPath('website_order_count', 0);
    }
}
