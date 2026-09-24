<table class="meta">
    <tr><td class="meta-label" dir="{{ $arabic ? 'rtl' : 'ltr' }}">{{ $labels['date'] }}</td><td class="meta-value" dir="ltr">{{ $invoiceDate ? date('Y-m-d', strtotime((string) $invoiceDate)) : '—' }}</td></tr>
    <tr><td class="meta-label" dir="{{ $arabic ? 'rtl' : 'ltr' }}">{{ $labels['invoice'] }}</td><td class="meta-value" dir="ltr">{{ $order->barcode }}</td></tr>
    <tr><td class="meta-label" dir="{{ $arabic ? 'rtl' : 'ltr' }}">{{ $labels['order'] }}</td><td class="meta-value" dir="ltr">{{ $order->id }}</td></tr>
</table>
