@php
    $identity = $invoiceIdentity ?? [
        'name' => $settings['title'] ?? config('app.name'), 'trn' => '', 'tax_enabled' => false,
    ];
    $arabic = $a4Profile['language'] === 'ar';
    $labels = $a4Profile['labels'];
    $contacts = config('invoices.one_way');
    $taxEnabled = (bool) ($identity['tax_enabled'] ?? false);
    $code = strtoupper((string) ($Currency ?? $currency ?? 'USD'));
    $precision = $code === 'SYP' ? 0 : 2;
    $money = fn ($amount) => number_format((float) $amount, $precision, '.', ',') . ' ' . $code;
    $buyerName = optional($order->buyer)->name ?: trim(($order->first_name ?? '') . ' ' . ($order->last_name ?? ''));
    $buyerPhone = optional($order->buyer)->phone ?: ($order->phone ?? '');
    $buyerAddress = optional($order->buyer)->address ?: ($order->address ?? '');
    $invoiceDate = $order->invoice_date ?: $order->created_at;
    $payment = (string) ($order->payment_type ?? '');
    $paymentKey = $payment === '2' ? 'cheque' : (in_array($payment, ['1', 'card', 'pay_by_card'], true) ? 'card' : ($payment === 'cod' ? 'cod' : 'cash'));
    $rows = [
        ['quantity', $items->sum('qty'), false],
        ['payment', $a4Profile['payments'][$paymentKey], true],
    ];
    if ((float) $order->discount > 0) {
        $rows[] = ['before_discount', $money($order->total_price_before_discount), false];
        $rows[] = ['discount', $money($order->discount), false];
    }
    if ($taxEnabled) {
        $rows[] = ['net', $money($order->price_without_tax), false];
        $rows[] = ['tax', $money($order->tax_value), false];
    }
    $rows[] = ['total', $money($order->total_price), false];
    if (isset($displayTotal) && $displayTotal !== null) {
        $rows[] = ['approx', '≈ ' . number_format($displayTotal, $displayDecimals, '.', ',') . ' ' . $displayCurrency, false];
    }
    $rows[] = ['paid', $money($order->paid_price), false];
    $rows[] = ['due', $money($order->remain_price), false];
    $notes = trim((string) ($order->notes ?? ''));
    // Long user notes must flow outside the nested summary table: mPDF otherwise
    // shrinks the whole table to fit a page or cannot break its oversized row.
    $longNotes = mb_strlen($notes) > 500 || substr_count($notes, "\n") > 5;
@endphp
<!DOCTYPE html>
<html lang="{{ $a4Profile['language'] }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $labels['title'] }} {{ $order->barcode }}</title>
    <style>
        @font-face { font-family: dejavusans; font-style: normal; font-weight: normal; src: url('{{ asset('custom/fonts/DejaVuSans.ttf') }}') format('truetype'); }
        @font-face { font-family: dejavusans; font-style: normal; font-weight: bold; src: url('{{ asset('custom/fonts/DejaVuSans-Bold.ttf') }}') format('truetype'); }
        /* mPDF requires numeric dimensions here; named CSS sizes collapse its page. */
        @page { size: 210mm 297mm; margin: 10mm; }
        body { margin: 0; color: {{ $a4Profile['ink'] }}; font-family: dejavusans, sans-serif; font-size: 10pt; line-height: 1.2; }
        * { box-sizing: border-box; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; direction: ltr; }
        td, th { vertical-align: top; }
        a { color: inherit; text-decoration: none; }
        .header { margin-bottom: 3mm; }
        .directory { width: 34%; padding-right: 3mm; font-size: 8.5pt; line-height: 1.14; }
        .branch { margin-bottom: 2mm; }
        .branch-name { font-weight: bold; }
        .branch-line { margin: 0; }
        .accent { color: {{ $a4Profile['accent'] }}; }
        .phone { direction: ltr; white-space: nowrap; }
        .online { margin-top: 3mm; font-size: 8pt; }
        .online p { margin: 0 0 1mm; }
        .brand { width: 39%; padding: 0 2mm; text-align: center; }
        .logo { width: 34mm; height: 34mm; }
        .legal-name { font-size: 12pt; line-height: 1.25; margin: 3mm 0; font-weight: bold; word-wrap: break-word; }
        .trn { font-size: 8pt; margin: 2mm 0; }
        .title { margin: 5mm 0 0; font-size: {{ $arabic ? '24' : '21' }}pt; font-weight: bold; }
        .header-syria .directory { padding-right: 0; padding-left: 3mm; }
        .header-syria .customer { padding-left: 0; padding-right: 2mm; }
        .header-syria .customer-meta { padding-top: 0; }
        .header-syria .title-cell { vertical-align: bottom; padding-top: 3mm; }
        .header-syria .title { margin: 0; }
        .customer { width: 27%; padding-left: 2mm; padding-top: 4mm; }
        .bill-to { background: {{ $a4Profile['pink'] }}; padding: 1mm 2mm; font-size: 11pt; font-weight: bold; }
        .buyer { font-size: 8.5pt; margin-top: 1mm; }
        .buyer td { border-bottom: .3pt solid {{ $a4Profile['border'] }}; padding: 2mm 1mm; word-wrap: break-word; }
        .buyer-label { width: 36%; font-weight: bold; white-space: nowrap; }
        .buyer-value { width: 64%; }
        .meta { margin-top: 4mm; font-size: 9pt; }
        .meta td { border: .6pt solid {{ $a4Profile['border'] }}; padding: 1.5mm; vertical-align: middle; }
        .meta-label { width: 44%; background: {{ $a4Profile['pink'] }}; }
        .meta-value { width: 56%; word-wrap: break-word; }
        .items { margin-bottom: 3mm; font-size: 10pt; }
        .items thead { display: table-header-group; }
        .items tr { page-break-inside: avoid; }
        .items th, .items td { border: .65pt solid {{ $a4Profile['border'] }}; padding: 1.6mm 1.3mm; vertical-align: middle; }
        .items th { background: {{ $a4Profile['pink'] }}; font-weight: bold; }
        .number { width: 7%; text-align: center; }
        .description { width: 41%; text-align: {{ $arabic ? 'right' : 'left' }}; word-wrap: break-word; }
        .qty { width: 10%; text-align: center; font-weight: bold; }
        .rate { width: 20%; }
        .amount { width: 22%; }
        .money { text-align: right; direction: ltr; white-space: nowrap; }
        .tax .description { width: 29%; }
        .tax .qty { width: 7%; }
        .tax .rate { width: 14%; }
        .tax .net { width: 15%; }
        .tax .vat { width: 13%; }
        .tax .amount { width: 15%; }
        .tax { font-size: 9pt; }
        .tax th { font-size: 8pt; }
        .lower { margin-top: 2mm; }
        .totals-column { width: 31%; padding-right: 3mm; }
        .terms-column { width: 69%; }
        .totals { font-size: 9pt; }
        .totals td { border: .6pt solid {{ $a4Profile['border'] }}; padding: 1.6mm 1.5mm; vertical-align: middle; }
        .total-label { width: 53%; background: {{ $a4Profile['pink'] }}; }
        .total-value { width: 47%; font-weight: bold; word-wrap: break-word; }
        .due { color: #dd142d; }
        .policy-heading { font-size: 11pt; margin: 0 0 1mm; text-align: right; }
        .policy { font-size: 8pt; margin: 0 0 2mm; line-height: 1.16; }
        .policy td { padding: .35mm 0; }
        .policy-number { width: 4%; text-align: center; }
        .policy-text { width: 96%; }
        .policy .arabic-policy { text-align: right; padding-right: 1mm; }
        .comments { margin-top: 2mm; font-size: 8pt; }
        .comments td { border: .6pt solid {{ $a4Profile['border'] }}; padding: 1.5mm; }
        .comments-label { width: 20%; background: {{ $a4Profile['pink'] }}; vertical-align: middle; }
        .comments-content { width: 80%; word-wrap: break-word; }
        .notice { margin: 0 0 1mm; color: {{ $a4Profile['accent'] }}; }
        .user-notes { word-wrap: break-word; }
        .extended-notes { margin-top: 3mm; font-size: 10pt; line-height: 1.3; }
        .extended-notes h3 { font-size: 11pt; border-bottom: .6pt solid {{ $a4Profile['border'] }}; }
        .summary-group { page-break-inside: avoid; }
        .sign-off { page-break-inside: avoid; margin-top: 2mm; }
        .signatures { font-size: 10pt; font-weight: bold; }
        .signature-left, .signature-right { width: 40%; }
        .signature-gap { width: 20%; }
        .signature-right { text-align: right; }
        .signature-space { height: 12mm; border-bottom: .6pt solid {{ $a4Profile['border'] }}; }
        .footer { margin-top: 2mm; font-size: 7pt; text-align: center; line-height: 1.25; }
        .footer strong { display: block; }
        @media screen { body { width: 210mm; min-height: 297mm; margin: 15px auto; padding: 10mm; background: white; box-shadow: 0 1px 8px #ccc; } }
        @media print { * { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }
    </style>
</head>
<body>
    @if($arabic)
        <table class="header header-syria" autosize="1" dir="ltr">
            <colgroup><col style="width: 27%"><col style="width: 39%"><col style="width: 34%"></colgroup>
            <tr>
                <td class="customer">@include('includes.invoice_a4.buyer')</td>
                <td class="brand">@include('includes.invoice_a4.brand')</td>
                <td class="directory" rowspan="2" align="right">@include('includes.invoice_a4.directory')</td>
            </tr>
            <tr>
                <td class="customer customer-meta">@include('includes.invoice_a4.metadata')</td>
                <td class="brand title-cell" valign="bottom"><div class="title" dir="rtl">{{ $taxEnabled ? $labels['tax_title'] : $labels['title'] }}</div></td>
            </tr>
        </table>
    @else
        <table class="header" autosize="1"><tr>
            <td class="directory" align="left">@include('includes.invoice_a4.directory')</td>
            <td class="brand">
                @include('includes.invoice_a4.brand')
                <div class="title" dir="ltr">{{ $taxEnabled ? $labels['tax_title'] : $labels['title'] }}</div>
            </td>
            <td class="customer">
                @include('includes.invoice_a4.buyer')
                @include('includes.invoice_a4.metadata')
            </td>
        </tr></table>
    @endif
    <table class="items{{ $taxEnabled ? ' tax' : '' }}" dir="ltr" autosize="1">
        <colgroup>
            <col style="width: 7%"><col style="width: {{ $taxEnabled ? 29 : 41 }}%"><col style="width: {{ $taxEnabled ? 7 : 10 }}%">
            <col style="width: {{ $taxEnabled ? 14 : 20 }}%">
            @if($taxEnabled)<col style="width: 15%"><col style="width: 13%">@endif
            <col style="width: {{ $taxEnabled ? 15 : 22 }}%">
        </colgroup>
        <thead>
            <tr>
                <th class="number">{{ $labels['number'] }}</th><th class="description">{{ $labels['description'] }}</th>
                <th class="qty">{{ $labels['qty'] }}</th><th class="rate">{{ $labels['rate'] }}</th>
                @if($taxEnabled)<th class="net">{{ $labels['excl'] }}</th><th class="vat">{{ $labels['vat'] }} <span dir="ltr">{{ (float) ($order->tax_ratio ?: optional($order->seller)->tax_ratio ?: 0) }}%</span></th>@endif
                <th class="amount">{{ $labels['amount'] }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
                <tr>
                    <td class="number">{{ $index + 1 }}</td>
                    <td class="description" dir="{{ $arabic ? 'rtl' : 'ltr' }}">{{ $arabic ? ($item->product_name ?? $item->name) : ($item->product_name_en ?? $item->name) }}@if(!empty($item->barcode)) - <span dir="ltr">{{ $item->barcode }}</span>@endif</td>
                    <td class="qty">{{ $item->qty }}</td>
                    <td class="money">{{ $money($taxEnabled ? $item->price_without_tax : $item->item_price) }}</td>
                    @if($taxEnabled)<td class="money">{{ $money($item->line_price_without_tax) }}</td><td class="money">{{ $money($item->line_tax_value) }}</td>@endif
                    <td class="money">{{ $money($item->total_price) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="summary-group">
    <table class="lower" autosize="1"><tr>
        <td class="totals-column">
            <table class="totals">
                @foreach($rows as [$key, $value, $isText])
                    <tr>
                        <td class="total-label" align="{{ $arabic ? 'right' : 'left' }}">@foreach($labels[$key] as $line)<div dir="{{ $loop->first ? 'rtl' : 'ltr' }}">{{ $line }}</div>@endforeach</td>
                        <td class="total-value{{ $key === 'due' ? ' due' : '' }}" dir="{{ $isText && $arabic ? 'rtl' : 'ltr' }}">{{ $value }}</td>
                    </tr>
                @endforeach
            </table>
        </td>
        <td class="terms-column" align="right">
            <h2 class="policy-heading" dir="rtl">سياسة التبديل</h2>
            <table class="policy" dir="ltr">
                @foreach(config('invoices.a4_policy.ar') as $term)
                    <tr><td class="policy-text arabic-policy" dir="rtl" align="right">{{ str_replace(':days', $a4Profile['collection_days'], $term) }}</td><td class="policy-number">{{ $loop->iteration }}.</td></tr>
                @endforeach
            </table>
            @if($a4Profile['english_policy'])
                <table class="policy" dir="ltr">
                    @foreach(config('invoices.a4_policy.en') as $term)
                        <tr><td class="policy-number" align="left">{{ $loop->iteration }}.</td><td class="policy-text" align="left">{{ str_replace(':days', $a4Profile['collection_days'], $term) }}</td></tr>
                    @endforeach
                </table>
            @endif
            <table class="comments"><tr>
                <td class="comments-label">@foreach($labels['comments'] as $line)<div>{{ $line }}</div>@endforeach</td>
                <td class="comments-content" align="{{ $arabic ? 'right' : 'left' }}">
                    @if($paymentKey === 'cheque')
                        @foreach($a4Profile['cheque_notice'] as $language => $notice)<p class="notice" dir="{{ $language === 'ar' ? 'rtl' : 'ltr' }}">{{ $notice }}</p>@endforeach
                    @endif
                    @if(!$longNotes)<div class="user-notes" dir="auto">{!! nl2br(e($notes)) !!}</div>@endif
                </td>
            </tr></table>
        </td>
    </tr></table>
    </div>
    @if($longNotes)
        <div class="extended-notes"><h3>{{ implode(' / ', $labels['comments']) }} · <span dir="ltr">{{ $order->barcode }}</span></h3><div class="user-notes" dir="auto">{!! nl2br(e($notes)) !!}</div></div>
    @endif
    <div class="sign-off">
        <table class="signatures" dir="ltr">
            <tr><td class="signature-left">{{ $labels['manager'] }}</td><td class="signature-gap"></td><td class="signature-right" align="right">{{ $labels['recipient'] }}</td></tr>
            <tr><td class="signature-space signature-left"></td><td class="signature-gap" style="border-bottom: 0"></td><td class="signature-space signature-right"></td></tr>
        </table>
        <div class="footer">
            <div dir="{{ $arabic ? 'rtl' : 'ltr' }}">{{ $a4Profile['footer'] }}</div>
            <div dir="ltr">{{ $contacts['footer_name'] }}, {{ $a4Profile['footer_phone'] }}, {{ $contacts['email'] }}</div>
            <strong>{{ $a4Profile['thanks'] }}</strong>
        </div>
    </div>
    @if(!empty($autoPrint))<script>window.addEventListener('load', function () { window.print(); });</script>@endif
</body>
</html>
