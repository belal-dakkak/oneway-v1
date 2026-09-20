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
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $order->barcode }}</title>
    <style>
        @page { size: A4; margin: 17px 24px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #202735; font: 11px/1.35 "DejaVu Sans", sans-serif; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td, th { vertical-align: top; }
        .header { margin-bottom: 8px; }
        .header td { padding: 3px 8px; }
        .header .contact { width: 32%; padding-left: 0; }
        .header .brand { width: 36%; text-align: center; padding: 0 6px; }
        .header .customer { width: 32%; padding-right: 0; }
        .brand img { display: block; width: 100px; height: 100px; margin: 0 auto 3px; }
        .brand-name { font-size: 14px; font-weight: bold; line-height: 1.25; word-wrap: break-word; }
        .eyebrow { color: #9d4559; font-size: 8px; font-weight: bold; letter-spacing: .7px; text-transform: uppercase; }
        .section-name { margin-bottom: 4px; padding: 4px 7px; background: #f9d7df; font-size: 10px; font-weight: bold; }
        .contact-name { margin-bottom: 4px; font-size: 11px; font-weight: bold; }
        .detail { margin: 0 0 3px; word-wrap: break-word; }
        .detail-label { color: #9d4559; font-size: 9px; font-weight: bold; }
        .detail-value { color: #202735; }
        .empty { color: #7c8290; }
        .ltr { direction: ltr; unicode-bidi: embed; }
        .document-heading { margin: 2px 0 7px; text-align: center; }
        .document-heading h1 { margin: 0; font-size: 20px; letter-spacing: 1px; line-height: 1.2; }
        .document-heading .subtitle { color: #7c8290; font-size: 9px; }
        .meta { margin-bottom: 10px; border: 1px solid #c7cbd3; }
        .meta td { width: 25%; padding: 4px 6px; border-right: 1px solid #c7cbd3; }
        .meta td:last-child { border-right: 0; }
        .meta .label { display: block; color: #9d4559; font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .meta .value { display: block; font-size: 10px; font-weight: bold; word-wrap: break-word; }
        .items { margin-bottom: 10px; }
        .items thead { display: table-header-group; }
        .items tr { page-break-inside: avoid; }
        .items th { padding: 5px 5px; border: 1px solid #aeb4bf; background: #f9d7df; text-align: left; font-size: 10px; }
        .items td { padding: 5px 5px; border: 1px solid #c7cbd3; }
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
        .lower { margin-bottom: 7px; }
        .lower > tbody > tr > td { vertical-align: top; }
        .totals-column { width: 35%; padding-right: 13px; }
        .terms-column { width: 65%; }
        .totals td { padding: 4px 5px; border: 1px solid #c7cbd3; vertical-align: middle; }
        .totals .total-label { width: 55%; background: #f9d7df; font-size: 10px; }
        .totals .total-value { text-align: right; font-size: 10px; word-wrap: break-word; }
        .totals .due { color: #aa344b; font-weight: bold; }
        .terms-title { margin: 0 0 4px; font-size: 11px; text-align: right; }
        .terms-list { margin: 0 0 5px; padding: 0 16px 0 21px; font-size: 9px; line-height: 1.25; }
        .terms-list li { margin-bottom: 2px; }
        .terms-list.ar { direction: rtl; text-align: right; padding: 0 21px 0 16px; }
        .note { width: 100%; margin-bottom: 10px; }
        .note td { padding: 5px 7px; border: 1px solid #c7cbd3; }
        .note .note-label { width: 20%; background: #f9d7df; font-weight: bold; }
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
            <td class="contact" dir="ltr">
                <div class="eyebrow">From / بيانات البائع</div>
                <div class="contact-name">{{ $invoiceIdentity['name'] }}</div>
                @if(!empty($invoiceIdentity['address']))
                    <p class="detail"><span class="detail-label">Address / العنوان</span><br><span class="detail-value">{{ $invoiceIdentity['address'] }}</span></p>
                @endif
                @if(!empty($invoiceIdentity['phone']))
                    <p class="detail"><span class="detail-label">Phone / الهاتف</span><br><span class="detail-value ltr">{{ $invoiceIdentity['phone'] }}</span></p>
                @endif
                @if(!empty($invoiceIdentity['email']))
                    <p class="detail"><span class="detail-label">Email / البريد</span><br><span class="detail-value">{{ $invoiceIdentity['email'] }}</span></p>
                @endif
                <p class="detail"><span class="detail-label">Website / الموقع</span><br><span class="detail-value">www.oneway.fashion</span></p>
            </td>
            <td class="brand">
                <img src="{{ $invoiceLogoSrc ?? public_path('custom/logo-icon-black.png') }}" alt="">
                <div class="brand-name">{{ $invoiceIdentity['name'] }}</div>
            </td>
            <td class="customer" dir="ltr">
                <div class="section-name">BILL TO / بيانات العميل</div>
                <p class="detail"><span class="detail-label">Name / الاسم</span><br><span class="detail-value">{{ $buyerName ?: '—' }}</span></p>
                @if($buyerPhone)
                    <p class="detail"><span class="detail-label">Phone / الهاتف</span><br><span class="detail-value ltr">{{ $buyerPhone }}</span></p>
                @endif
                @if($buyerAddress)
                    <p class="detail"><span class="detail-label">Address / العنوان</span><br><span class="detail-value">{{ $buyerAddress }}</span></p>
                @endif
                @if($taxEnabled && !empty($order->trn))
                    <p class="detail"><span class="detail-label">Customer TRN / الرقم الضريبي للعميل</span><br><span class="detail-value">{{ $order->trn }}</span></p>
                @endif
            </td>
        </tr>
    </table>

    <div class="document-heading">
        <h1>{{ $taxEnabled ? 'TAX INVOICE' : 'INVOICE' }}</h1>
        <div class="subtitle">{{ $taxEnabled ? 'فاتورة ضريبية' : 'فاتورة' }}</div>
    </div>

    <table class="meta">
        <tr>
            <td><span class="label">Date / التاريخ</span><span class="value">{{ $invoiceDate ? date('Y-m-d', strtotime((string) $invoiceDate)) : '—' }}</span></td>
            <td><span class="label">Invoice # / رقم الفاتورة</span><span class="value">{{ $order->barcode }}</span></td>
            <td><span class="label">Order ID / رقم الطلب</span><span class="value">{{ $order->id }}</span></td>
            <td>
                @if($taxEnabled && !empty($invoiceIdentity['trn']))
                    <span class="label">TRN / الرقم الضريبي</span><span class="value">{{ $invoiceIdentity['trn'] }}</span>
                @else
                    <span class="label">Currency / العملة</span><span class="value">{{ $currencyCode }}</span>
                @endif
            </td>
        </tr>
    </table>

    <table class="items{{ $taxEnabled ? ' tax' : '' }}" dir="ltr">
        <thead>
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

    @if(trim((string) ($order->notes ?? '')) !== '')
        <table class="note"><tr><td class="note-label">ملاحظات<br>Comments</td><td>{{ $order->notes }}</td></tr></table>
    @endif

    <table class="signatures">
        <tr>
            <td>Manager / المدير</td>
            <td>Recipient / المستلم</td>
        </tr>
    </table>
    <div class="footer">
        <div>If you have any questions about this invoice, please contact us.</div>
        <div class="footer-contact">{{ $invoiceIdentity['name'] }}@if(!empty($invoiceIdentity['phone'])) · {{ $invoiceIdentity['phone'] }}@endif @if(!empty($invoiceIdentity['email'])) · {{ $invoiceIdentity['email'] }}@endif</div>
        <strong>Thank You For Your Business!</strong>
    </div>
@if(!empty($autoPrint))
<script>window.addEventListener('load', function () { window.print(); });</script>
@endif
</body>
</html>
