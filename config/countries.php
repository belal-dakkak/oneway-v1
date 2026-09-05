<?php

return [
    'global_product_country_id' => 3,

    'countries' => [
        'LB' => [
            'id' => 1,
            'name' => 'Lebanon',
            'name_ar' => 'لبنان',
            'timezone' => 'Asia/Beirut',
            'phone_code' => '961',
            'admin_currencies' => ['USD', 'LP'],
            'storefront_currencies' => ['USD'],
            'default_currency' => 'USD',
            'storefront' => true,
        ],
        'AE' => [
            'id' => 2,
            'name' => 'United Arab Emirates',
            'name_ar' => 'الإمارات العربية المتحدة',
            'timezone' => 'Asia/Dubai',
            'phone_code' => '971',
            'admin_currencies' => ['AED'],
            'storefront_currencies' => ['AED', 'USD'],
            'default_currency' => 'AED',
            'storefront' => true,
        ],
        'SY' => [
            'id' => 4,
            'name' => 'Syria',
            'name_ar' => 'سوريا',
            'timezone' => 'Asia/Damascus',
            'phone_code' => '963',
            'admin_currencies' => ['USD'],
            'storefront_currencies' => ['USD'],
            'default_currency' => 'USD',
            'base_currency' => 'USD',
            'display_currency' => 'SYP',
            'storefront' => true,
        ],
        'TR' => [
            'id' => null,
            'name' => 'Turkey',
            'name_ar' => 'تركيا',
            'timezone' => 'Europe/Istanbul',
            'phone_code' => '90',
            'admin_currencies' => [],
            'storefront_currencies' => [],
            'default_currency' => null,
            'storefront' => false,
        ],
    ],
];
