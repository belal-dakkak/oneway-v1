@if($arabic)
    @foreach($a4Profile['branches'] as $branch)
        <div class="branch">
            <div class="branch-name accent" dir="rtl">{{ $branch['country'] }}</div>
            @if(isset($branch['address']))<div class="accent" dir="rtl">{{ $branch['address'] }}</div>@endif
            @foreach($branch['phones'] as $phone)<div class="phone"><span dir="ltr">{{ $phone }}</span></div>@endforeach
        </div>
    @endforeach
@else
    @foreach($contacts['branches'] as $branch)
        <div class="branch">
            <div class="branch-name">{{ $branch['country'] }}</div>
            @foreach($branch['details'] as $detail)
                <div class="branch-line"><span class="accent">{{ $detail['label'] }} :</span> {{ $detail['value'] }}</div>
            @endforeach
        </div>
    @endforeach
@endif
<div class="online">
    <p><span class="accent">{{ $labels['website'] }} :</span> <a href="{{ $contacts['website_url'] }}" dir="ltr">{{ $contacts['website'] }}</a></p>
    <p><span class="accent">{{ $labels['email'] }} :</span> <a href="mailto:{{ $contacts['email'] }}" dir="ltr">{{ $contacts['email'] }}</a></p>
</div>
