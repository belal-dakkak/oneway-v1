<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\WebsiteContactService;
use App\Support\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteContactServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_directory_contains_every_approved_number_in_the_same_order(): void
    {
        $directory = app(WebsiteContactService::class)->directory();

        $this->assertSame(['AE', 'SY', 'LB', 'TR'], array_column($directory, 'code'));
        $this->assertSame([
            '+971 545 516 995',
            '+971 564 533 655',
            '+963 958 900 555',
            '+963 947 900 555',
            '+961 81 730 725',
            '+905 004 001 621',
        ], collect($directory)->flatMap(function (array $branch) {
            return array_column($branch['phones'], 'number');
        })->values()->all());
        $this->assertSame([
            'https://wa.me/971545516995',
            'https://wa.me/971564533655',
            'https://wa.me/963958900555',
            'https://wa.me/963947900555',
            'https://wa.me/96181730725',
            'https://wa.me/905004001621',
        ], collect($directory)->flatMap(function (array $branch) {
            return array_column($branch['phones'], 'whatsapp_url');
        })->values()->all());
    }

    public function test_whatsapp_links_are_country_specific_and_unconfigured_numbers_are_hidden(): void
    {
        $key = Setting::keyColumn();
        Setting::query()->create(['country' => Country::UAE, 'language' => 'en', $key => 'whatsapp', 'value' => '+971 50 123 4567']);
        Setting::query()->create(['country' => Country::SYRIA, 'language' => 'ar', $key => 'whatsapp', 'value' => '00963 944 123 456']);
        Setting::query()->create(['country' => Country::SYRIA, 'language' => 'en', $key => 'email', 'value' => 'syria@example.test']);
        Setting::query()->create(['country' => Country::LEBANON, 'language' => 'en', $key => 'whatsapp', 'value' => 'invalid']);

        $contacts = app(WebsiteContactService::class)->forStorefrontCountries();

        $this->assertSame('https://wa.me/971501234567', $contacts['AE']['whatsapp_url']);
        $this->assertSame('+963947900555', $contacts['SY']['whatsapp']);
        $this->assertSame('https://wa.me/963947900555', $contacts['SY']['whatsapp_url']);
        $this->assertSame('syria@example.test', $contacts['SY']['email']);
        $this->assertNull($contacts['LB']['whatsapp_url']);
        $this->assertArrayNotHasKey('TR', $contacts);
    }

    public function test_country_settings_form_saves_contacts_on_the_existing_key_schema(): void
    {
        $admin = User::query()->create([
            'name' => 'Settings Admin', 'email' => 'settings-admin@example.test',
            'password' => 'secret', 'role_id' => User::ROLE_ADMIN,
            'country_id' => Country::SYRIA,
        ]);

        $this->actingAs($admin)->post(route('settings.store'), [
            'settinglanguage' => 'en',
            'title' => 'Syrian site',
            'phone' => '+963 944 111 222',
            'email' => 'syria@example.test',
            'whatsapp' => '+963 944 111 222',
        ])->assertRedirect(route('settings.index'));

        $this->actingAs($admin)->get(route('settings.edit', 'en'))->assertOk();
        $this->assertSame('https://wa.me/963947900555',
            app(WebsiteContactService::class)->forStorefrontCountries()['SY']['whatsapp_url']);
    }
}
