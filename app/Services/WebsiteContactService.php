<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\Country;

class WebsiteContactService
{
    /** @return array<string, array{whatsapp: string, whatsapp_url: ?string, email: string}> */
    public function forStorefrontCountries(): array
    {
        $keyColumn = Setting::keyColumn();
        $settings = Setting::query()
            ->whereIn('country', Country::allowedIds())
            ->whereIn('language', ['en', 'ar'])
            ->whereIn($keyColumn, ['whatsapp', 'email'])
            ->get()
            ->groupBy('country');

        $contacts = [];
        foreach (Country::storefront() as $code => $country) {
            $rows = $settings->get((int) $country['id'], collect());
            $value = function (string $name) use ($rows, $keyColumn): string {
                foreach (['en', 'ar'] as $language) {
                    $setting = $rows->first(function (Setting $row) use ($name, $language, $keyColumn) {
                        return $row->{$keyColumn} === $name && $row->language === $language && trim((string) $row->value) !== '';
                    });
                    if ($setting) {
                        return trim((string) $setting->value);
                    }
                }
                return '';
            };

            $whatsapp = $value('whatsapp');
            $digits = preg_replace('/\D+/', '', $whatsapp);
            if (str_starts_with($digits, '00')) {
                $digits = substr($digits, 2);
            }
            $contacts[$code] = [
                'whatsapp' => $whatsapp,
                'whatsapp_url' => preg_match('/^[1-9][0-9]{7,14}$/', $digits) ? 'https://wa.me/' . $digits : null,
                'email' => $value('email'),
            ];
        }

        return $contacts;
    }
}
