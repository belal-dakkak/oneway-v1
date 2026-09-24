<div class="bill-to">BILL TO</div>
<table class="buyer">
    <tr><td class="buyer-label">Name:</td><td class="buyer-value" dir="auto">{{ $buyerName ?: '—' }}</td></tr>
    <tr><td class="buyer-label">Phone:</td><td class="buyer-value" dir="ltr">{{ $buyerPhone ?: '—' }}</td></tr>
    <tr><td class="buyer-label">Address:</td><td class="buyer-value" dir="auto">{{ $buyerAddress ?: '—' }}</td></tr>
    @if($taxEnabled && !empty($order->trn))<tr><td>TRN:</td><td dir="ltr">{{ $order->trn }}</td></tr>@endif
</table>
