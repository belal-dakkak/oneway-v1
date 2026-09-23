@php
    $countryId = (int) ($invoiceCountryId ?? ($order instanceof \App\Models\WebsiteOrder ? $order->country_id : optional($order->seller)->country_id));
    $a4Profile = config('invoices.a4_countries.' . $countryId);
@endphp
@if($a4Profile)
    @include('includes.invoice_country_a4')
@else
@php
    $invoiceIdentity = $invoiceIdentity ?? [
        'name' => $settings['title'] ?? config('app.name'),
        'address' => $settings['address'] ?? '',
        'phone' => $settings['phone'] ?? '',
        'email' => $settings['email'] ?? '',
        'trn' => '',
        'tax_enabled' => false,
    ];
    $taxEnabled = (bool) ($invoiceIdentity['tax_enabled'] ?? false);
    $currencyCode = strtoupper((string) ($Currency ?? $currency ?? 'USD'));
    $moneyDecimals = $currencyCode === 'SYP' ? 0 : 2;
    $quantity = $items->sum('qty');
    $buyerName = trim((string) (optional($order->buyer)->name ?: trim(($order->first_name ?? '') . ' ' . ($order->last_name ?? ''))));
    $buyerPhone = optional($order->buyer)->phone ?: ($order->phone ?? '');
    $buyerAddress = optional($order->buyer)->address ?: ($order->address ?? '');
    $invoiceDate = $order->invoice_date ?: $order->created_at;
    $paymentType = (string) ($order->payment_type ?? '');
    $paymentLabel = $paymentType === '2' ? 'Cheque'
        : (in_array($paymentType, ['1', 'card', 'pay_by_card'], true) ? 'Credit / debit card'
        : ($paymentType === 'cod' ? 'Cash on delivery' : 'Cash'));
    $policy = getRefundPolicy();
    $brandContacts = config('invoices.one_way');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $order->barcode }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; color: #202735; font: 10px/1.28 dejavusans, sans-serif; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td, th { vertical-align: top; }
        a { color: inherit; text-decoration: none; }
        .header { margin-bottom: 7px; }
        .header > tbody > tr > td { padding: 0 7px; }
        .header .contact { width: 35%; padding-left: 0; }
        .header .brand { width: 40%; text-align: center; }
        .header .customer { width: 25%; padding-right: 0; }
        .contact-directory { direction: ltr; font-size: 8.5px; line-height: 1.18; }
        .contact-group { margin: 0 0 3px; }
        .contact-country { font-size: 9px; font-weight: bold; }
        .contact-line { margin: 0; white-space: nowrap; }
        .contact-label { color: #d13d58; font-weight: bold; }
        .contact-online { margin-top: 5px; }
        .brand img { display: block; width: 30mm; height: 30mm; margin: 0 auto 3px; object-fit: contain; }
        .brand-name { font-size: 14px; font-weight: bold; line-height: 1.25; text-transform: uppercase; word-wrap: break-word; }
        .brand-trn { margin-top: 4px; font-size: 9px; }
        .invoice-title { margin: 9px 0 0; font-size: 20px; letter-spacing: .7px; line-height: 1.05; }
        .invoice-subtitle { margin-top: 2px; font-size: 9px; font-weight: normal; }
        .section-name { display: inline-block; margin-bottom: 4px; padding: 3px 7px; background: #f4bdca; font-size: 10px; font-weight: bold; }
        .detail { margin: 0 0 4px; word-wrap: break-word; }
        .detail-label { color: #202735; font-size: 9px; font-weight: bold; }
        .detail-value { color: #202735; }
        .ltr { direction: ltr; unicode-bidi: embed; }
        .meta { margin-top: 11px; border: 1px solid #747b86; }
        .meta td { padding: 3px 5px; border: 1px solid #747b86; vertical-align: middle; }
        .meta .label { width: 43%; background: #f4bdca; font-size: 8.5px; font-weight: bold; }
        .meta .value { width: 57%; font-size: 8.5px; font-weight: bold; word-wrap: break-word; }
        .items { margin-bottom: 10px; }
        .items thead { display: table-header-group; }
        .items tr { page-break-inside: avoid; }
        .items .repeated-reference th { padding: 2px 0 4px; border: 0; background: #fff; color: #606776; text-align: right; font-size: 8px; }
        .items th { padding: 5px; border: 1px solid #747b86; background: #f4bdca; text-align: left; font-size: 10px; }
        .items td { padding: 5px; border: 1px solid #8b929d; }
        .items .number { width: 7%; text-align: center; }
        .items .description { width: 43%; overflow-wrap: break-word; }
        .items .qty { width: 10%; text-align: center; }
        .items .money { text-align: right; white-space: nowrap; }
        .items .rate { width: 19%; }
        .items .amount { width: 21%; }
        .items.tax .description { width: 29%; }
        .items.tax .qty { width: 8%; }
        .items.tax .rate { width: 12%; }
        .items.tax .amount { width: 17%; }
        .items.tax .tax-amount { width: 13%; }
        .items.tax .tax-base { width: 14%; }
        .closing { page-break-inside: avoid; }
        .summary-reference { margin: 0 0 4px; color: #606776; font-size: 8px; text-align: right; }
        .lower { margin-bottom: 7px; }
        .lower > tbody > tr > td { vertical-align: top; }
        .totals-column { width: 35%; padding-right: 13px; }
        .terms-column { width: 65%; }
        .totals td { padding: 4px 5px; border: 1px solid #8b929d; vertical-align: middle; }
        .totals .total-label { width: 55%; background: #f4bdca; font-size: 10px; }
        .totals .total-value { text-align: right; font-size: 10px; word-wrap: break-word; }
        .totals .due { color: #aa344b; font-weight: bold; }
        .terms-title { margin: 0 0 4px; font-size: 11px; text-align: right; }
        .terms-list { margin: 0 0 5px; padding: 0 16px 0 21px; font-size: 9px; line-height: 1.25; }
        .terms-list li { margin-bottom: 2px; }
        .terms-list.ar { direction: rtl; text-align: right; padding: 0 21px 0 16px; }
        .note { width: 100%; margin-bottom: 10px; }
        .note td { padding: 5px 7px; border: 1px solid #8b929d; }
        .note .note-label { width: 20%; background: #f4bdca; font-weight: bold; }
        .signatures { margin-bottom: 7px; }
        .signatures td { width: 50%; padding: 0 8px 13px; border-bottom: 1px solid #c7cbd3; font-size: 10px; font-weight: bold; }
        .signatures td:first-child { padding-left: 0; }
        .signatures td:last-child { padding-right: 0; text-align: right; }
        .footer { padding-top: 5px; text-align: center; color: #606776; font-size: 8px; }
        .footer strong { display: block; margin-top: 4px; color: #202735; font-size: 10px; }
        .footer-contact { word-wrap: break-word; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td class="contact">
                <div class="contact-directory">
                    @foreach($brandContacts['branches'] as $branch)
                        <div class="contact-group">
                            <div class="contact-country">{{ $branch['country'] }}</div>
                            @foreach($branch['details'] as $detail)
                                <div class="contact-line"><span class="contact-label">{{ $detail['label'] }} :</span> {{ $detail['value'] }}</div>
                            @endforeach
                        </div>
                    @endforeach
                    <div class="contact-online">
                        <div class="contact-line"><span class="contact-label">Website :</span> <a href="{{ $brandContacts['website_url'] }}">{{ $brandContacts['website'] }}</a></div>
                        <div class="contact-line"><span class="contact-label">Email :</span> <a href="mailto:{{ $brandContacts['email'] }}">{{ $brandContacts['email'] }}</a></div>
                    </div>
                </div>
            </td>
            <td class="brand">
                <img src="{{ $invoiceLogoSrc ?? public_path('custom/logo-icon-black.png') }}" width="95" height="95" alt="">
                <div class="brand-name">{{ $invoiceIdentity['name'] }}</div>
                @if($taxEnabled && !empty($invoiceIdentity['trn']))
                    <div class="brand-trn"><strong>TRN:</strong> {{ $invoiceIdentity['trn'] }}</div>
                @endif
                <h1 class="invoice-title">{{ $taxEnabled ? 'TAX INVOICE' : 'INVOICE' }}</h1>
                <div class="invoice-subtitle">{{ $taxEnabled ? 'فاتورة ضريبية' : 'فاتورة' }}</div>
            </td>
            <td class="customer" dir="ltr">
                <div class="section-name">BILL TO / بيانات العميل</div>
                <p class="detail"><span class="detail-label">Name / الاسم:</span> <span class="detail-value">{{ $buyerName ?: '—' }}</span></p>
                @if($buyerPhone)
                    <p class="detail"><span class="detail-label">Phone / الهاتف:</span> <span class="detail-value ltr">{{ $buyerPhone }}</span></p>
                @endif
                @if($buyerAddress)
                    <p class="detail"><span class="detail-label">Address / العنوان:</span> <span class="detail-value">{{ $buyerAddress }}</span></p>
                @endif
                @if($taxEnabled && !empty($order->trn))
                    <p class="detail"><span class="detail-label">Customer TRN:</span> <span class="detail-value">{{ $order->trn }}</span></p>
                @endif
                <table class="meta">
                    <tr><td class="label">Date</td><td class="value">{{ $invoiceDate ? date('Y-m-d', strtotime((string) $invoiceDate)) : '—' }}</td></tr>
                    <tr><td class="label">Invoice #</td><td class="value">{{ $order->barcode }}</td></tr>
                    <tr><td class="label">Order ID</td><td class="value">{{ $order->id }}</td></tr>
                    <tr><td class="label">Currency</td><td class="value">{{ $currencyCode }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items{{ $taxEnabled ? ' tax' : '' }}" dir="ltr">
        <thead>
            <tr class="repeated-reference"><th colspan="{{ $taxEnabled ? 7 : 5 }}">Invoice # {{ $order->barcode }} · {{ $currencyCode }}</th></tr>
            <tr>
                <th class="number">NO.</th>
                <th class="description">DESCRIPTION / الوصف</th>
                <th class="qty">QTY / الكمية</th>
                <th class="rate money">RATE / السعر</th>
                @if($taxEnabled)
                    <th class="tax-base money">EXCL. VAT</th>
                    <th class="tax-amount money">VAT @ {{ (float) ($order->tax_ratio ?: optional($order->seller)->tax_ratio ?: 0) }}%</th>
                @endif
                <th class="amount money">AMOUNT / المبلغ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
                <tr>
                    <td class="number">{{ $index + 1 }}</td>
                    <td class="description">{{ $item->name }}</td>
                    <td class="qty">{{ $item->qty }}</td>
                    <td class="money">{{ number_format($taxEnabled ? $item->price_without_tax : $item->item_price, $moneyDecimals) }} {{ $currencyCode }}</td>
                    @if($taxEnabled)
                        <td class="money">{{ number_format($item->line_price_without_tax, $moneyDecimals) }} {{ $currencyCode }}</td>
                        <td class="money">{{ number_format($item->line_tax_value, $moneyDecimals) }} {{ $currencyCode }}</td>
                    @endif
                    <td class="money">{{ number_format($item->total_price, $moneyDecimals) }} {{ $currencyCode }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="closing">
    <div class="summary-reference">Invoice # {{ $order->barcode }} · Summary</div>
    <table class="lower">
        <tr>
            <td class="totals-column">
                <table class="totals">
                    <tr><td class="total-label">إجمالي القطع<br>Total Qty</td><td class="total-value">{{ $quantity }}</td></tr>
                    <tr><td class="total-label">طريقة الدفع<br>Payment Method</td><td class="total-value">{{ $paymentLabel }}</td></tr>
                    @if((float) ($order->discount ?? 0) > 0)
                        <tr><td class="total-label">قبل الخصم<br>Before Discount</td><td class="total-value">{{ number_format($order->total_price_before_discount, $moneyDecimals) }} {{ $currencyCode }}</td></tr>
                        <tr><td class="total-label">الخصم<br>Discount</td><td class="total-value">{{ number_format($order->discount, $moneyDecimals) }} {{ $currencyCode }}</td></tr>
                    @endif
                    @if($taxEnabled)
                        <tr><td class="total-label">دون الضريبة<br>Excl. VAT</td><td class="total-value">{{ number_format($order->price_without_tax, $moneyDecimals) }} {{ $currencyCode }}</td></tr>
                        <tr><td class="total-label">الضريبة<br>Total VAT</td><td class="total-value">{{ number_format($order->tax_value, $moneyDecimals) }} {{ $currencyCode }}</td></tr>
                    @endif
                    <tr><td class="total-label">إجمالي الفاتورة<br>Total Bill</td><td class="total-value">{{ number_format($order->total_price, $moneyDecimals) }} {{ $currencyCode }}</td></tr>
                    @if(isset($displayTotal) && $displayTotal !== null)
                        <tr><td class="total-label">للعرض فقط<br>Approx. Value</td><td class="total-value">≈ {{ number_format($displayTotal, $displayDecimals, '.', ',') }} {{ $displayCurrency }}</td></tr>
                    @endif
                    <tr><td class="total-label">المدفوع<br>Total Payments</td><td class="total-value">{{ number_format($order->paid_price, $moneyDecimals) }} {{ $currencyCode }}</td></tr>
                    <tr><td class="total-label">الباقي<br>Amount Due</td><td class="total-value due">{{ number_format($order->remain_price, $moneyDecimals) }} {{ $currencyCode }}</td></tr>
                </table>
            </td>
            <td class="terms-column">
                <h2 class="terms-title">سياسة الاستبدال / Exchange Policy</h2>
                <ol class="terms-list ar" lang="ar" dir="rtl">
                    @foreach($policy['ar']['terms'] as $term)
                        <li>{{ $term }}</li>
                    @endforeach
                </ol>
                <ol class="terms-list" lang="en" dir="ltr">
                    @foreach($policy['en']['terms'] as $term)
                        <li>{{ $term }}</li>
                    @endforeach
                </ol>
            </td>
        </tr>
    </table>

    <table class="note"><tr><td class="note-label">ملاحظات<br>Comments</td><td>{{ $order->notes ?? '' }}</td></tr></table>

    <table class="signatures">
        <tr>
            <td>Manager / المدير</td>
            <td>Recipient / المستلم</td>
        </tr>
    </table>
    <div class="footer">
        <div>If you have any questions about this invoice, please contact</div>
        <div class="footer-contact">{{ $brandContacts['footer_name'] }}, {{ $brandContacts['branches'][0]['details'][1]['value'] }}, {{ $brandContacts['email'] }}</div>
        <strong>Thank You For Your Business!</strong>
    </div>
    </div>
@if(!empty($autoPrint))
<script>window.addEventListener('load', function () { window.print(); });</script>
@endif
</body>
</html>
@endif
