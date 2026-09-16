<td width="35%" style="border:0; text-align:{{ $isSyriaInvoice ? 'right' : 'left' }}; vertical-align:top;" dir="rtl">
    <strong>{{ $invoiceIdentity['name'] }}</strong><br>
    @if($shop_address)<span style="color:red">Address {{ $isSyriaInvoice ? '/ العنوان' : '' }}</span> : {{ $shop_address }}<br>@endif
    @if($admin_mobile)<span style="color:red">Phone {{ $isSyriaInvoice ? '/ الهاتف' : '' }}</span> : {{ $admin_mobile }}<br>@endif
    @if($admin_email)<span style="color:red">Email {{ $isSyriaInvoice ? '/ البريد الإلكتروني' : '' }}</span> : {{ $admin_email }}<br>@endif
</td>
