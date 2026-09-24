<?php

return [
    // A4 presentation only; the receipt and other countries keep their existing policy.
    'a4_countries' => [
        2 => [
            'language' => 'en', 'ink' => '#111111', 'accent' => '#d52040',
            'pink' => '#fac5d2', 'border' => '#555555', 'footer_phone' => '+971 545 516 995',
            'labels' => [
                'title' => 'INVOICE', 'tax_title' => 'TAX INVOICE',
                'date' => 'Date', 'invoice' => 'Invoice #', 'order' => 'Order ID',
                'number' => 'NO.', 'description' => 'DESCRIPTION', 'qty' => 'QTY',
                'rate' => 'RATE', 'amount' => 'AMOUNT', 'excl' => 'EXCL. VAT', 'vat' => 'VAT',
                'quantity' => ['إجمالي عدد القطع', 'Total Qty'],
                'payment' => ['طريقة الدفع', 'Payment Method'],
                'before_discount' => ['قبل الخصم', 'Before Discount'], 'discount' => ['الخصم', 'Discount'],
                'net' => ['دون الضريبة', 'Excl. VAT'], 'tax' => ['إجمالي الضريبة', 'Total VAT'],
                'total' => ['إجمالي الفاتورة', 'Total bill'], 'paid' => ['إجمالي المدفوعات', 'Total payments'],
                'due' => ['الباقي', 'Amount due'], 'approx' => ['للعرض فقط', 'Approx. Value'],
                'comments' => ['ملاحظات', 'Comments'], 'manager' => 'Manager / المدير',
                'recipient' => 'Recipient / المستلم',
                'website' => 'Website', 'email' => 'Email',
            ],
            'payments' => ['cash' => 'Pay by Cash', 'card' => 'Pay by Credit/Debit Card', 'cheque' => 'Pay by Cheque', 'cod' => 'Cash on delivery'],
            'collection_days' => 5,
            'english_policy' => true,
            'cheque_notice' => [
                'en' => 'Important Notice: According to the agreement, the check is due 90 days after the invoice date, and this period is fixed and cannot be modified under any circumstances.',
                'ar' => 'تنبيه هام: وفقاً للاتفاق، تستحق الشيكات بعد 90 يوماً من تاريخ الفاتورة، وهذه المدة ثابتة لا يمكن تعديلها تحت أي ظرف من الظروف.',
            ],
            'footer' => 'If you have any question about this invoice, please contact',
            'thanks' => 'Thank You For Your Business!',
        ],
        4 => [
            'language' => 'ar', 'ink' => '#111111', 'accent' => '#d52040',
            'pink' => '#fac5d2', 'border' => '#555555', 'footer_phone' => '+963 958 900 555',
            'labels' => [
                'title' => 'فاتورة', 'tax_title' => 'فاتورة ضريبية',
                'date' => 'التاريخ', 'invoice' => 'رقم الفاتورة', 'order' => 'رقم الطلب',
                'number' => 'الرقم', 'description' => 'الوصف', 'qty' => 'الكمية',
                'rate' => 'سعر الوحدة', 'amount' => 'المبلغ', 'excl' => 'دون الضريبة', 'vat' => 'الضريبة',
                'quantity' => ['إجمالي عدد القطع'], 'payment' => ['طريقة الدفع'],
                'before_discount' => ['قبل الخصم'], 'discount' => ['الخصم'],
                'net' => ['دون الضريبة'], 'tax' => ['إجمالي الضريبة'],
                'total' => ['إجمالي الفاتورة'], 'paid' => ['إجمالي المدفوعات'], 'due' => ['الباقي'],
                'approx' => ['للعرض فقط'], 'comments' => ['ملاحظات'],
                'manager' => 'المدير', 'recipient' => 'المستلم',
                'website' => 'الموقع الإلكتروني', 'email' => 'البريد الإلكتروني',
            ],
            'payments' => ['cash' => 'دفع نقدي', 'card' => 'دفع بالبطاقة', 'cheque' => 'دفع عن طريق الشيك', 'cod' => 'الدفع عند الاستلام'],
            'collection_days' => 3,
            'english_policy' => false,
            'cheque_notice' => [
                'ar' => 'ملاحظة هامة: وفقاً للاتفاق، تستحق الشيكات بعد 90 يوماً من تاريخ الفاتورة، والمدة لا يمكن تمديدها تحت أي ظرف من الظروف.',
            ],
            'footer' => 'إذا كان لديكم أي استفسار حول هذه الفاتورة، يرجى التواصل معنا',
            'thanks' => 'شكراً لتعاملكم مع وان واي',
            'branches' => [
                ['country' => 'الفرع الأول: الإمارات - عجمان فقط', 'address' => 'الفرع - عجمان - المنطقة الصناعية 2 - بيروت', 'phones' => ['+971 545 516 995', '+971 564 533 655']],
                ['country' => 'الفرع الثاني: سوريا - حلب', 'phones' => ['+963 958 900 555', '+963 947 900 555']],
                ['country' => 'الفرع الثالث: لبنان - بيروت', 'phones' => ['+961 81 730 725']],
                ['country' => 'الفرع الرابع: تركيا - اسطنبول - مارتر', 'phones' => ['+905 004 001 621']],
            ],
        ],
    ],
    'a4_policy' => [
        'ar' => [
            'مدة الاستبدال 3 أيام من تاريخ الفاتورة.',
            'يجب أن تكون البضاعة بحالتها الأصلية مع إرفاق الفاتورة.',
            'لا يمكن استبدال أو استرجاع البضاعة بعد مرور الفترة المحددة.',
            'يشمل الاستبدال المنتجات المعيبة فقط.',
            'يجب استلام البضاعة خلال :days أيام من تاريخ الطلب وإلا يلغى الطلب بدون استرجاع العربون.',
        ],
        'en' => [
            'The replacement period is 3 days from the date of the invoice.',
            'The goods to be exchanged must be in good condition, presentable with their packaging and label, and accompanied by the original purchase receipt.',
            'The goods to be replaced must not conform to standard specifications or have a defect that is not apparent upon purchase.',
            'We do not offer returns or cash refunds.',
            'The order must be received within a maximum period of :days days from the date of the order, otherwise the order will be canceled without refunding the deposit.',
        ],
    ],
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
