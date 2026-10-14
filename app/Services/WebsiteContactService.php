<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\Country;

class WebsiteContactService
{
    /**
     * @return array<int, array{
     *     code: string,
     *     name: array{ar: string, en: string},
     *     address: array{ar: string, en: string},
     *     map_url: string,
     *     phones: array<int, array{number: string, whatsapp_url: string}>
     * }>
     */
    public function directory(): array
    {
        return collect(config('website_contacts.branches', []))
            ->map(function (array $branch): array {
                $branch['phones'] = collect($branch['phones'] ?? [])
                    ->map(function (string $number): array {
                        $digits = $this->internationalDigits($number);

                        return [
                            'number' => $number,
                            'whatsapp_url' => 'https://wa.me/'.$digits,
                        ];
                    })
                    ->values()
                    ->all();

                return $branch;
            })
            ->values()
            ->all();
    }

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
            $digits = $this->internationalDigits($whatsapp);
            $contacts[$code] = [
                'whatsapp' => $whatsapp,
                'whatsapp_url' => preg_match('/^[1-9][0-9]{7,14}$/', $digits) ? 'https://wa.me/' . $digits : null,
                'email' => $value('email'),
            ];
        }

        return $contacts;
    }

    private function internationalDigits(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?: '';
        return str_starts_with($digits, '00') ? substr($digits, 2) : $digits;
    }
}
