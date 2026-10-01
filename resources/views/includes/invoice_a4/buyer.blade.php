<div class="bill-to">BILL TO</div>
<table class="buyer">
    <tr><td class="buyer-label">Name:</td><td class="buyer-value" dir="auto">{{ $buyerName ?: '—' }}</td></tr>
    <tr><td class="buyer-label">Phone:</td><td class="buyer-value" dir="ltr">{{ $buyerPhone ?: '—' }}</td></tr>
    <tr><td class="buyer-label">Address:</td><td class="buyer-value" dir="auto">{{ $buyerAddress ?: '—' }}</td></tr>
    @if(!empty($customerTrn ?? app(\App\Services\InvoiceDataService::class)->customerTrn($order)))<tr><td>TRN:</td><td dir="ltr">{{ $customerTrn ?? app(\App\Services\InvoiceDataService::class)->customerTrn($order) }}</td></tr>@endif
</table>
