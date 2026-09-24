<img class="logo" src="{{ $invoiceLogoSrc ?? public_path('custom/logo-icon-black.png') }}" alt="One Way">
<div class="legal-name" dir="auto">{{ $identity['name'] }}</div>
@if($taxEnabled && !empty($identity['trn']))<div class="trn">{{ $arabic ? 'الرقم الضريبي' : 'TRN' }}: <span dir="ltr">{{ $identity['trn'] }}</span></div>@endif
