<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>One Way</title>
        <meta name="description" content="{{ $meta_description ?? 'Welcome to One Way' }}" />
        <meta property="og:title" content="{{ $meta_title ?? 'One Way' }}" />
        <meta property="og:url" content="{{ url()->current() }}" />
        <meta property="og:description" content="{{ $meta_description ?? 'Welcome to One Way' }}" />
        <meta property="og:image" content="{{ $meta_image ?? asset('custom/logo-icon.png') }}" />
        <meta property="og:type" content="product" />
        <meta property="og:site_name" content="One Way" />
        
        <!-- Twitter Meta Tags -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $meta_title ?? 'One Way' }}">
        <meta name="twitter:description" content="{{ $meta_description ?? 'Welcome to One Way' }}">
        <meta name="twitter:image" content="{{ $meta_image ?? asset('custom/logo-icon.png') }}">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Arabic:wght@100..900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

        <!-- Styles -->
        <link rel="stylesheet" href="{{ mix('css/app.css') }}">

        <!-- Scripts -->
        @routes
        <script src="{{ mix('js/app.js') }}" defer></script>
        @inertiaHead

        <!-- Facebook Pixel Code -->
        <script>
            !function(f,b,e,v,n,t,s)
            {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
            n.callMethod.apply(n,arguments):n.queue.push(arguments)};
            if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
            n.queue=[];t=b.createElement(e);t.async=!0;
            t.src=v;s=b.getElementsByTagName(e)[0];
            s.parentNode.insertBefore(t,s)}(window,document,'script',
            'https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', '1020617740025461');
            fbq('track', 'PageView');
        </script>
        <noscript>
            <img height="1" width="1" style="display:none"
                src="https://www.facebook.com/tr?id=1020617740025461&ev=PageView&noscript=1"/>
        </noscript>
        <!-- End Facebook Pixel Code -->
    </head>
    <body class="antialiased" style="font-family: 'Noto Sans Arabic', sans-serif;">

        @inertia

        @env ('local')
            <script src="http://localhost:3000/browser-sync/browser-sync-client.js"></script>
        @endenv

        <script>
            window.app_url = '{{ asset('/') }}';
        </script>
    </body>
</html>
