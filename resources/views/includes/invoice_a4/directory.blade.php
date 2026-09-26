@foreach($a4Profile['branches'] as $branch)
    <div class="branch">
        <div class="branch-name accent" dir="{{ $arabic ? 'rtl' : 'ltr' }}">{{ $branch['country'] }}</div>
        @foreach($branch['phones'] as $phone)<div class="phone"><span dir="ltr">{{ $phone }}</span></div>@endforeach
    </div>
@endforeach
<div class="online">
    <p><span class="accent">{{ $labels['website'] }} :</span> <a href="{{ $contacts['website_url'] }}" dir="ltr">{{ $contacts['website'] }}</a></p>
    <p><span class="accent">{{ $labels['email'] }} :</span> <a href="mailto:{{ $contacts['email'] }}" dir="ltr">{{ $contacts['email'] }}</a></p>
</div>
