<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPhoneFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_be_filtered_by_a_phone_number_regardless_of_formatting(): void
    {
        $admin = User::factory()->create([
            'country_id' => User::COUNTRY_UAE,
            'role_id' => User::ROLE_ADMIN,
        ]);
        $matching = User::factory()->create([
            'country_id' => User::COUNTRY_UAE,
            'role_id' => User::ROLE_CLIENT,
            'phone' => '+971 50-123 4567',
        ]);
        User::factory()->create([
            'country_id' => User::COUNTRY_UAE,
            'role_id' => User::ROLE_CLIENT,
            'phone' => '+971 55 999 0000',
        ]);
        User::factory()->create([
            'country_id' => User::COUNTRY_SYRIA,
            'role_id' => User::ROLE_CLIENT,
            'phone' => '+971 50 123 4567',
        ]);

        $response = $this->actingAs($admin)->getJson(route('users.index', [
            'type' => User::ROLE_CLIENT,
            'phone' => '(050) 123-4567',
        ]));

        $response->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $matching->id);
    }

    public function test_phone_filter_is_preserved_in_pagination_links(): void
    {
        $admin = User::factory()->create([
            'country_id' => User::COUNTRY_UAE,
            'role_id' => User::ROLE_ADMIN,
        ]);

        User::factory()->count(11)->create([
            'country_id' => User::COUNTRY_UAE,
            'role_id' => User::ROLE_CLIENT,
            'phone' => '+971 56 453 3655',
        ]);

        $response = $this->actingAs($admin)->getJson(route('users.index', [
            'type' => User::ROLE_CLIENT,
            'phone' => '564533655',
        ]));

        $response->assertOk()->assertJsonPath('total', 11);
        $this->assertStringContainsString(
            'phone=564533655',
            urldecode((string) $response->json('next_page_url'))
        );
    }
}
