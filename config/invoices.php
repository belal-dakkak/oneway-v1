<?php

return [
    'one_way' => [
        'branches' => [
            [
                'country' => 'United Arab Emirates',
                'details' => [
                    ['label' => 'Branch 1', 'value' => 'Ajman Industrial 2 Beirut Street'],
                    ['label' => 'Phone', 'value' => '+971 545 516 995'],
                    ['label' => 'Phone', 'value' => '+971 564 533 655'],
                ],
            ],
            [
                'country' => 'Syria (Aleppo)',
                'details' => [
                    ['label' => 'Branch 2', 'value' => 'Aleppo'],
                    ['label' => 'Phone', 'value' => '+963 947 900 555'],
                    ['label' => 'Phone', 'value' => '+963 958 900 555'],
                ],
            ],
            [
                'country' => 'Lebanon, Beirut',
                'details' => [
                    ['label' => 'Branch 3', 'value' => 'Lebanon Beirut'],
                    ['label' => 'Phone', 'value' => '+961 81 730 725'],
                ],
            ],
            [
                'country' => 'Türkiye',
                'details' => [
                    ['label' => 'Branch 4', 'value' => 'Türkiye Istanbul Merter'],
                    ['label' => 'Phone', 'value' => '+905 004 001 621'],
                ],
            ],
        ],
        'website' => 'www.oneway.fashion',
        'website_url' => 'http://www.oneway.fashion',
        'email' => 'theoneway.fashion@gmail.com',
        'footer_name' => 'ONE WAY CLOTHING TRADING',
    ],

    'date' => [
        /*
         * Carbon date format
         */
        'format' => 'Y-m-d',
        /*
         * Due date for payment since invoice's date.
         */
        'pay_until_days' => 7,
    ],

    'serial_number' => [
        'series'   => 'AA',
        'sequence' => 1,
        /*
         * Sequence will be padded accordingly, for ex. 00001
         */
        'sequence_padding' => 5,
        'delimiter'        => '.',
        /*
         * Supported tags {SERIES}, {DELIMITER}, {SEQUENCE}
         * Example: AA.00001
         */
        'format' => '{SERIES}{DELIMITER}{SEQUENCE}',
    ],

    'currency' => [
        'code' => 'eur',
        /*
         * Usually cents
         * Used when spelling out the amount and if your currency has decimals.
         *
         * Example: Amount in words: Eight hundred fifty thousand sixty-eight EUR and fifteen ct.
         */
        'fraction' => 'ct.',
        'symbol'   => '€',
        /*
         * Example: 19.00
         */
        'decimals' => 2,
        /*
         * Example: 1.99
         */
        'decimal_point' => '.',
        /*
         * By default empty.
         * Example: 1,999.00
         */
        'thousands_separator' => '',
        /*
         * Supported tags {VALUE}, {SYMBOL}, {CODE}
         * Example: 1.99 €
         */
        'format' => '{VALUE} {SYMBOL}',
    ],

    'paper' => [
        // A4 = 210 mm x 297 mm = 595 pt x 842 pt
        'size'        => 'a4',
        'orientation' => 'portrait',
    ],

    'disk' => 'local',

    'seller' => [
        /*
         * Class used in templates via $invoice->seller
         *
         * Must implement LaravelDaily\Invoices\Contracts\PartyContract
         *      or extend LaravelDaily\Invoices\Classes\Party
         */
        'class' => \LaravelDaily\Invoices\Classes\Seller::class,

        /*
         * Default attributes for Seller::class
         */
        'attributes' => [
            'name'          => 'Towne, Smith and Ebert',
            'address'       => '89982 Pfeffer Falls Damianstad, CO 66972-8160',
            'code'          => '41-1985581',
            'vat'           => '123456789',
            'phone'         => '760-355-3930',
            'custom_fields' => [
                /*
                 * Custom attributes for Seller::class
                 *
                 * Used to display additional info on Seller section in invoice
                 * attribute => value
                 */
                'SWIFT' => 'BANK101',
            ],
        ],
    ],
];
