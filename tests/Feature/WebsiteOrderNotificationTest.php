<?php

namespace Tests\Feature;

use App\Jobs\DispatchWebsiteOrderNotifications;
use App\Mail\NewOrderAdminEmail;
use App\Mail\OrderConfirmationEmail;
use App\Models\User;
use App\Models\WebsiteOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    public function test_pending_count_is_independent_of_unread_notification_ids(): void
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
                'status' => WebsiteOrder::STATUS_PENDING,
            ]);
            $service->deliver($order);
            $order->update(['notifications_sent_at' => null]);
            $service->deliver($order); // deterministic ID protects even a retried marker
        }
        $this->actingAs($user)->getJson(route('notification.summary'))->assertOk()
            ->assertJsonPath('website_order_count', 8)->assertJsonCount(8, 'website_order_ids')
            ->assertJsonCount(5, 'notifications');
        $user->unreadNotifications()->first()->markAsRead();
        $this->getJson(route('notification.summary'))->assertJsonPath('website_order_count', 8)
            ->assertJsonCount(7, 'website_order_ids');
        $user->update(['country_id' => User::COUNTRY_SYRIA]);
        $this->getJson(route('notification.summary'))->assertJsonPath('website_order_count', 0);
    }

    public function test_badge_tracks_status_changes_for_all_viewers_and_includes_orders_without_notifications(): void
    {
        Mail::fake();
        $shop = $this->badgeUser('shop', User::ROLE_SHOP, User::COUNTRY_UAE);
        $other = $this->badgeUser('warehouse', User::ROLE_WAREHOUSE, User::COUNTRY_UAE);
        $admin = $this->badgeUser('admin', User::ROLE_ADMIN, User::COUNTRY_UAE);
        $orders = collect(range(1, 3))->map(fn ($i) => $this->badgeOrder('PENDING-' . $i));
        foreach ([WebsiteOrder::STATUS_UNPAID, WebsiteOrder::STATUS_ONGOING,
            WebsiteOrder::STATUS_DELIVERED, WebsiteOrder::STATUS_FAILED] as $status) {
            $this->badgeOrder('EXCLUDED-' . $status, $status);
        }
        $foreign = $this->badgeOrder('FOREIGN', WebsiteOrder::STATUS_PENDING, User::COUNTRY_SYRIA);

        $this->actingAs($shop)->getJson(route('notification.summary', ['search' => 'nothing', 'date' => '2000-01-01']))
            ->assertJsonPath('website_order_count', 3)->assertJsonCount(0, 'website_order_ids');
        foreach ($orders as $index => $order) {
            $this->actingAs($shop)->postJson(route('orders.websiteOrders.changeStatus', $order->id),
                ['status' => WebsiteOrder::STATUS_ONGOING])->assertOk();
            foreach ([$shop, $other, $admin] as $viewer) {
                $this->actingAs($viewer)->getJson(route('notification.summary'))
                    ->assertJsonPath('website_order_count', 2 - $index);
            }
        }
        $this->actingAs($shop)->postJson(route('orders.websiteOrders.changeStatus', $orders[0]->id),
            ['status' => WebsiteOrder::STATUS_PENDING])->assertOk();
        $this->postJson(route('orders.websiteOrders.changeStatus', $orders[0]->id), ['status' => 99])->assertStatus(422);
        $this->postJson(route('orders.websiteOrders.changeStatus', $foreign->id),
            ['status' => WebsiteOrder::STATUS_ONGOING])->assertNotFound();
        $this->getJson(route('notification.summary'))->assertJsonPath('website_order_count', 1);
        $admin->update(['country_id' => User::COUNTRY_SYRIA]);
        $this->actingAs($admin)->getJson(route('notification.summary'))->assertJsonPath('website_order_count', 1);
    }

    public function test_opening_list_or_reading_one_notification_does_not_clear_other_staff_notifications_or_badge(): void
    {
        config(['broadcasting.default' => 'log']);
        $shop = $this->badgeUser('reader', User::ROLE_SHOP, User::COUNTRY_UAE);
        $other = $this->badgeUser('colleague', User::ROLE_WAREHOUSE, User::COUNTRY_UAE);
        $order = $this->badgeOrder('READ-ORDER');
        app(\App\Services\WebsiteOrderNotificationService::class)->deliver($order);
        $this->actingAs($shop)->getJson(route('orders.websiteOrders'))->assertOk();
        $this->assertSame(1, $shop->unreadNotifications()->count());
        $this->assertSame(1, $other->unreadNotifications()->count());
        $this->get(route('notification.order.show', ['notification' => $shop->notifications()->first()->id]))->assertOk();
        $this->assertSame(0, $shop->unreadNotifications()->count());
        $this->assertSame(1, $other->unreadNotifications()->count());
        $this->getJson(route('notification.summary'))->assertJsonPath('website_order_count', 1)
            ->assertJsonCount(0, 'website_order_ids');
    }

    public function test_cod_status_and_paid_tracking_never_post_website_money_to_the_cashbox(): void
    {
        Mail::fake();
        $shop = $this->badgeUser('cashbox-free', User::ROLE_SHOP, User::COUNTRY_UAE);
        $order = $this->badgeOrder('TRACK-ONLY');
        $order->update(['paid_price' => 0, 'remain_price' => 55]);

        foreach ([WebsiteOrder::STATUS_ONGOING, WebsiteOrder::STATUS_DELIVERED] as $status) {
            $this->actingAs($shop)->postJson(route('orders.websiteOrders.changeStatus', $order->id), [
                'status' => $status,
            ])->assertOk()->assertJsonPath('status', $status);
        }

        $order->refresh();
        $this->assertSame(0.0, (float) $order->paid_price);
        $this->assertSame(55.0, (float) $order->remain_price);
        $this->assertFalse($order->is_paid);
        $this->assertNull($order->cashbox_posted_at);

        $this->postJson(route('orders.websiteOrders.markPaid', $order->id))
            ->assertOk()
            ->assertJsonPath('paid_price', 55)
            ->assertJsonPath('remain_price', 0)
            ->assertJsonPath('is_paid', true);

        $order->refresh();
        $this->assertTrue($order->is_paid);
        $this->assertNull($order->cashbox_posted_at);
        $this->assertSame(0, DB::table('wallet_movements')->count());
    }

    private function badgeUser(string $name, int $role, int $country): User
    {
        return User::query()->create(['name' => $name, 'email' => $name . '@badge.example.test',
            'password' => 'x', 'role_id' => $role, 'country_id' => $country]);
    }

    private function badgeOrder(string $barcode, int $status = WebsiteOrder::STATUS_PENDING, int $country = User::COUNTRY_UAE): WebsiteOrder
    {
        return WebsiteOrder::query()->create(['barcode' => $barcode, 'country_id' => $country,
            'payment_type' => 'cod', 'curr_type' => 'AED', 'total_price' => 55, 'status' => $status,
            'email' => 'buyer@example.test', 'phone' => '+971501234567']);
    }
}
