<td width="25%" style="border:0; text-align:{{ $isSyriaInvoice ? 'left' : 'right' }}; vertical-align:top;" dir="{{ $isSyriaInvoice ? 'ltr' : 'rtl' }}">
    <div class="bg_color1" style="font-size:14px; line-height:24px; color:black;">BILL TO {{ $isSyriaInvoice ? '/ إلى' : '' }}</div>
    <div>Name {{ $isSyriaInvoice ? '/ الاسم' : '' }}: {{ optional($order->buyer)->name ?? trim(($order->first_name ?? '').' '.($order->last_name ?? '')) ?: 'No name' }}</div>
    <div>Phone {{ $isSyriaInvoice ? '/ الهاتف' : '' }}: {{ optional($order->buyer)->phone ?? $order->phone ?? 'No phone' }}</div>
    <div>Address {{ $isSyriaInvoice ? '/ العنوان' : '' }}: {{ optional($order->buyer)->address ?? $order->address ?? 'No address' }}</div>
</td>
