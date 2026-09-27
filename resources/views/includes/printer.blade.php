@php
    $invoiceIdentity = $invoiceIdentity ?? [
        'name' => $settings['title'] ?? config('app.name'), 'trn' => '', 'tax_enabled' => false,
    ];
    $taxEnabled = (bool) $invoiceIdentity['tax_enabled'];
    $countryId = (int) ($invoiceCountryId ?? ($order->country_id ?? optional($order->seller)->country_id));
    $arabicBranches = $countryId === \App\Support\Country::SYRIA;
    $profile = config('invoices.a4_countries.' . ($arabicBranches ? \App\Support\Country::SYRIA : \App\Support\Country::UAE));
    $contacts = config('invoices.one_way');
    $Currency = strtoupper($Currency ?? $currency ?? $order->curr_type ?? 'USD');
    $moneyDecimals = $Currency === 'SYP' ? 0 : 2;
    $money = static fn ($value) => number_format((float) $value, $moneyDecimals, '.', ',');
    $buyerName = optional($order->buyer)->name ?: trim(($order->first_name ?? '') . ' ' . ($order->last_name ?? ''));
    $taxRatio = $order->tax_ratio ?? optional($order->seller)->tax_ratio;
    $qty = $items->sum('qty');
    $payment = (string) ($order->payment_type ?? '0');
    $paymentKey = $payment === '2' ? 'cheque' : (in_array($payment, ['1', 'card', 'pay_by_card'], true) ? 'card' : ($payment === 'cod' ? 'cod' : 'cash'));
    $paymentLabel = config('invoices.a4_countries.' . \App\Support\Country::UAE . '.payments.' . $paymentKey);
    $summarySpan = $taxEnabled ? 3 : 2;
    $createdAt = \Carbon\Carbon::parse($order->created_at);
    $summary = [];
    if ($taxEnabled) {
        $summary[] = ['الإجمالي بدون الضريبة / Total bill Excl. VAT', $order->price_without_tax ?? ((float) $order->total_price - (float) $order->tax_value)];
    }
    foreach (['discount' => 'الخصم / Discount', 'shipping_fee' => 'رسوم الشحن / Shipping Fee', 'cod_fee' => 'رسوم الدفع عند الاستلام / COD Fee'] as $field => $label) {
        if ((float) $order->{$field} > 0) $summary[] = [$label, $order->{$field}];
    }
    if ($taxEnabled) $summary[] = ['إجمالي الضريبة / Total VAT', $order->tax_value];
    $summary[] = [$taxEnabled ? 'الإجمالي شامل الضريبة / Total bill Incl. VAT' : 'الإجمالي / Total bill', $order->total_price];
    $summary[] = ['المدفوع / Paid', $order->paid_price];
    $summary[] = ['المتبقي / Remaining', $order->remain_price];
@endphp
<!DOCTYPE html>
<html lang="ar" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order {{ $order->id }}</title>
    <style>
        @font-face {
            font-family: 'Receipt DejaVu';
            src: url('{{ asset('custom/fonts/DejaVuSans-Bold.ttf') }}') format('truetype');
            font-weight: 700;
        }
        @page { size: 71.12mm 279.4mm; margin: 2mm; }
        :root { font-size: 24px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #000; background: #fff; font-family: Arial, 'Receipt DejaVu', sans-serif; font-weight: 700; line-height: 1.25; }
        #bodyContent { width: 595px; max-width: 100%; padding: .4rem; margin: .5rem auto; }
        p { margin: 0; }
        a { color: inherit; text-decoration: none; overflow-wrap: anywhere; }
        h1 { font-size: 1.25rem; text-align: center; margin: 1rem 0; }
        hr { border: 0; border-top: 2px dotted #000; margin: .8rem 0; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { font-size: inherit; font-weight: 700; overflow-wrap: anywhere; vertical-align: middle; }
        .trn { text-align: center; margin: 1.5rem 0; }
        .brand { width: 27%; text-align: center; padding-right: .3rem; }
        .brand img { display: block; width: 100%; height: auto; margin-top: 1rem; }
        .directory { font-size: .9rem; }
        .branch { margin-bottom: .45rem; }
        .branch p { margin-bottom: .1rem; }
        .phone { direction: ltr; unicode-bidi: isolate; white-space: nowrap; }
        .contact { font-size: .8rem; margin-top: .35rem; }
        .operation { text-align: center; padding: .6rem 0; }
        .date-time { text-align: center; font-size: .85rem; }
        .identity { direction: rtl; margin: .6rem 0; }
        .identity th { width: 29%; text-align: right; }
        .identity td { text-align: center; padding: .2rem; }
        .items { text-align: center; }
        .items th, .items td { border: 1px solid #000; padding: .25rem .15rem; }
        .items thead { display: table-header-group; text-transform: uppercase; }
        .items thead th { font-size: .9rem; }
        .items .description { text-align: left; }
        .items .money { direction: ltr; }
        .summary-label { direction: rtl; }
        tr { break-inside: avoid; page-break-inside: avoid; }
        .barcode { margin: .8rem 0; text-align: center; break-inside: avoid; }
        .barcode img { display: block; width: 32%; height: auto; margin: 0 auto; }
        .barcode p { font-size: .8rem; letter-spacing: .1rem; margin-top: .25rem; overflow-wrap: anywhere; }
        .browse { display: flex; align-items: flex-start; gap: .6rem; margin: .8rem 0 2rem; break-inside: avoid; }
        .qr { flex: 0 0 30%; }
        .qr img { width: 100%; height: auto; display: block; }
        .browse-info { flex: 1; min-width: 0; text-align: center; }
        .browse-info p { margin-bottom: .65rem; }
        .policy h2 { text-align: center; font-size: 1.15rem; margin: 1rem 0 .5rem; border-bottom: 1px solid #000; padding-bottom: .3rem; break-after: avoid; }
        .policy ul { padding-inline-start: 1.3rem; margin: .5rem 0 1rem; }
        .policy li { margin-bottom: .5rem; line-height: 1.4; }
        .thanks { text-align: center; margin-top: 1rem; break-inside: avoid; }
        @media print {
            :root { font-size: 10px; }
            #bodyContent { width: 100%; max-width: none; margin: 0; padding: 0; }
            .items thead th { font-size: .85rem; }
        }
    </style>
</head>
<body>
<main id="bodyContent">
    <h1>@if($taxEnabled) TAX INVOICE / <span dir="rtl">فاتورة ضريبية</span> @else Receipt / <span dir="rtl">فاتورة</span> @endif</h1>
    @if($taxEnabled && $invoiceIdentity['trn'])<p class="trn" dir="ltr">TRN {{ $invoiceIdentity['trn'] }}</p>@endif
    <hr>
    <table class="bill-details">
        <tr>
            <td class="brand">One Way<img src="{{ asset('custom/logo-icon-black.png') }}" alt="One Way"></td>
            <td class="directory" dir="{{ $arabicBranches ? 'rtl' : 'ltr' }}">
                @foreach($profile['branches'] as $branch)
                    <div class="branch">
                        <p>{{ $branch['country'] }}</p>
                        @foreach($branch['phones'] as $phone)<p><span class="phone">{{ $phone }}</span></p>@endforeach
                    </div>
                @endforeach
                <p class="contact">{{ $profile['labels']['website'] }}: <a dir="ltr" href="{{ $contacts['website_url'] }}">{{ $contacts['website'] }}</a></p>
                <p class="contact">{{ $profile['labels']['email'] }}: <a dir="ltr" href="mailto:{{ $contacts['email'] }}">{{ $contacts['email'] }}</a></p>
            </td>
        </tr>
    </table>
    <hr>
    <p class="operation"><bdi>{{ $order->id }}</bdi> Op.No / <span dir="rtl">رقم العملية</span></p>
    <hr>
    <table class="date-time"><tr>
        <td>{{ $createdAt->format('h:i A') }}</td><td>Time / الوقت</td>
        <td>{{ $createdAt->format('Y-m-d') }}</td><td>Date / التاريخ</td>
    </tr></table>
    <hr>
    <table class="identity">
        <tr><th>العميل / Client</th><td>{{ $buyerName }}</td></tr>
        @if($taxEnabled)<tr><th>Customer TRN</th><td dir="ltr">{{ $order->trn }}</td></tr>@endif
        <tr><th>البائع / Seller</th><td>{{ $invoiceIdentity['name'] }}</td></tr>
        <tr><th>الشاحن / Shipper</th><td>{{ optional($order->shipper)->name }}</td></tr>
    </table>
    <hr>
    <table class="items">
        @if($taxEnabled)
            <colgroup><col style="width:20%"><col style="width:10%"><col style="width:15%"><col style="width:22%"><col style="width:13%"><col style="width:20%"></colgroup>
        @else
            <colgroup><col style="width:40%"><col style="width:10%"><col style="width:25%"><col style="width:25%"></colgroup>
        @endif
        <thead><tr>
            <th>Model</th><th>Qty</th><th>Rate</th>
            @if($taxEnabled)<th>Amount<br>Excl. VAT</th><th>VAT @if($taxRatio !== null)<br>@ {{ $taxRatio }}%@endif</th><th>Amount<br>Incl. VAT</th>@else<th>Amount</th>@endif
        </tr></thead>
        <tbody>
            @foreach($items as $item)
                <tr class="item-row">
                    <td class="description"><bdi>{{ $item->name }}</bdi></td><td>{{ $item->qty }}</td>
                    <td class="money">{{ $money($item->item_price) }} {{ $Currency }}</td>
                    @if($taxEnabled)
                        <td class="money">{{ $money($item->line_price_without_tax) }} {{ $Currency }}</td>
                        <td class="money">{{ $money($item->line_tax_value) }} {{ $Currency }}</td>
                    @endif
                    <td class="money">{{ $money($item->total_price) }} {{ $Currency }}</td>
                </tr>
            @endforeach
            @foreach($summary as [$label, $value])
                <tr><th colspan="{{ $summarySpan }}" class="summary-label">{{ $label }}</th><td colspan="{{ $summarySpan }}" class="money">{{ $money($value) }} {{ $Currency }}</td></tr>
            @endforeach
            @if(isset($displayTotal))
                <tr><th colspan="{{ $summarySpan }}" class="summary-label">المقابل التقريبي (للعرض فقط) / Approximate display value</th><td colspan="{{ $summarySpan }}" class="money">≈ {{ number_format($displayTotal, $displayDecimals, '.', ',') }} {{ $displayCurrency }}</td></tr>
            @endif
            <tr><th colspan="{{ $summarySpan }}" class="summary-label">إجمالي العدد / Total Qty</th><td colspan="{{ $summarySpan }}">{{ $qty }}</td></tr>
            <tr><th colspan="{{ $summarySpan }}" class="summary-label">طريقة الدفع / Payment Method</th><td colspan="{{ $summarySpan }}">{{ $paymentLabel }}</td></tr>
        </tbody>
    </table>
    @if(!empty($order->barcode))
        <div class="barcode" dir="ltr">
            <img alt="Barcode {{ $order->barcode }}" src="data:image/svg+xml;base64,{{ base64_encode(DNS1D::getBarcodeSVG($order->barcode, 'C128', 2, 60, '#000000', false)) }}">
            <p>{{ $order->barcode }}</p>
        </div>
    @endif
    <hr>
    <div class="browse">
        <div class="qr"><img alt="One Way website QR code" src="data:image/svg+xml;base64,{{ base64_encode(DNS2D::getBarcodeSVG('https://www.oneway.fashion/', 'QRCODE', 7, 7)) }}"></div>
        <div class="browse-info">
            <p>{{ $createdAt->format('Y-m-d h:i A') }}</p>
            <p>لتصفح الموديلات<br>To Browse Models</p>
            <a href="https://www.oneway.fashion/">https://www.oneway.fashion/</a>
        </div>
    </div>
    @foreach(['ar', 'en'] as $language)
        <section class="policy" lang="{{ $language }}" dir="{{ $language === 'ar' ? 'rtl' : 'ltr' }}">
            <h2>{{ config('invoices.a4_policy_headings.' . $language) }}</h2>
            <ul>
                @foreach(config('invoices.a4_policy.' . $language) as $term)
                    <li>{{ str_replace(':days', '5', $term) }}</li>
                @endforeach
            </ul>
        </section>
    @endforeach
    <div class="thanks">
        <p dir="rtl">{{ config('invoices.a4_countries.' . \App\Support\Country::SYRIA . '.thanks') }}</p>
        <p>{{ config('invoices.a4_countries.' . \App\Support\Country::UAE . '.thanks') }}</p>
    </div>
</main>
<script>
    window.addEventListener('load', async function () {
        if (document.fonts && document.fonts.ready) await document.fonts.ready;
        await Promise.all(Array.from(document.images).map(function (img) {
            return img.decode ? img.decode().catch(function () {}) : Promise.resolve();
        }));
        window.print();
    }, { once: true });
</script>
</body>
</html>
