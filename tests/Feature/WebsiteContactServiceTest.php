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

    public function test_whatsapp_links_are_country_specific_and_unconfigured_numbers_are_hidden(): void
    {
        $key = Setting::keyColumn();
        Setting::query()->create(['country' => Country::UAE, 'language' => 'en', $key => 'whatsapp', 'value' => '+971 50 123 4567']);
        Setting::query()->create(['country' => Country::SYRIA, 'language' => 'ar', $key => 'whatsapp', 'value' => '00963 944 123 456']);
        Setting::query()->create(['country' => Country::SYRIA, 'language' => 'en', $key => 'email', 'value' => 'syria@example.test']);
        Setting::query()->create(['country' => Country::LEBANON, 'language' => 'en', $key => 'whatsapp', 'value' => 'invalid']);

        $contacts = app(WebsiteContactService::class)->forStorefrontCountries();

        $this->assertSame('https://wa.me/971501234567', $contacts['AE']['whatsapp_url']);
        $this->assertSame('https://wa.me/963944123456', $contacts['SY']['whatsapp_url']);
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
        $this->assertSame('https://wa.me/963944111222',
            app(WebsiteContactService::class)->forStorefrontCountries()['SY']['whatsapp_url']);
    }
}
