<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="author"
        content="Style - The Impressive Fashion Shopify Theme complies with contemporary standards. Meet The Most Impressive Fashion Style Theme Ever. Well Toasted, and Well Tested.">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0" />
    <title>@yield('page-title')
        </title>
    <meta name="base-url" content="{{ URL::to('/') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
  
    {!! metaKeywordSetting($metakeyword ?? '',$metadesc ?? '',$metaimage ?? '',$slug) !!}

<link rel="shortcut icon"
    href="{{ isset($theme_favicon) && !empty($theme_favicon) ? $theme_favicon : asset('themes/' . $currentTheme  . '/assets/images/Favicon.png') }}" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100;200;300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
        <link
        href="https://fonts.googleapis.com/css2?family=Dela+Gothic+One&family=Outfit:wght@100;200;300;400;500;600;700;800;900&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="shortcut icon" href="{{ asset('themes/' . $currentTheme . '/assets/images/favicon.png') }}">
   
    <link rel="stylesheet" href="{{ asset('themes/' . $currentTheme . '/assets/css/main-style.css') }}">
    <link rel="stylesheet" href="{{ asset('themes/' . $currentTheme . '/assets/css/responsive.css') }}">
    @if($currantLang == 'ar' || $currantLang == 'he')
        <link rel="stylesheet" href="{{ asset('themes/' . $currentTheme .  '/assets/css/rtl-main-style.css') }}">
        <link rel="stylesheet" href="{{ asset('themes/' . $currentTheme .  '/assets/css/rtl-responsive.css') }}">
        <link rel="stylesheet" href="{{ asset('css/rtl-custom.css') }}">
    @else
        <link rel="stylesheet" href="{{ asset('themes/' . $currentTheme .  '/assets/css/responsive.css') }}">
        <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('public/assets/fonts/fontawesome.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/plugins/notifier.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/tabler-icons.min.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/floating-wpp.min.css') }}">

    
    <link rel="stylesheet" href="{{ asset('public/assets/fonts/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/css/customizer.css') }}">

    {{-- pwa customer app --}}
    <meta name="mobile-wep-app-capable" content="yes">
    <meta name="apple-mobile-wep-app-capable" content="yes">
    <meta name="msapplication-starturl" content="/">
    <link rel="apple-touch-icon" href="{{ isset($theme_favicon) && !empty($theme_favicon) ? $theme_favicons : 'themes/' . $currentTheme . '/assets/images/Favicon.png' }}">
    @if ($store->enable_pwa_store == 'on')
        <link rel="manifest"
            href="{{ asset('storage/uploads/customer_app/store_' . $store->id . '/manifest.json') }}" />
    @endif
    @if (!empty($store->pwa_store($store->slug)->theme_color))
        <meta name="theme-color" content="{{ $store->pwa_store($store->slug)->theme_color }}" />
    @endif

</head>

<body>
    @if(isset($pixelScript))
        @foreach ($pixelScript as $script)
            <?= $script ?>
        @endforeach
    @endif
    @yield('content')
    <div class="modal modal-popup" id="commanModel" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-inner lg-dialog" role="document">
            <div class="modal-content">
                <div class="popup-content">
                    <div class="modal-header  popup-header align-items-center">
                        <div class="modal-title">
                            <h6 class="mb-0" id="modelCommanModelLabel"></h6>
                        </div>
                        <button type="button" class="close close-button" data-bs-dismiss="modal"
                            aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!--scripts start here-->
    <script src="{{ asset('themes/' . $currentTheme . '/assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('themes/' . $currentTheme . '/assets/js/slick.min.js') }}" defer="defer"></script>
    <script src="{{ asset('themes/' . $currentTheme . '/assets/js/custom.js') }}" defer="defer"></script>
    
    <!-- Include jQuery Validation plugin from a CDN -->
    <script src="{{ asset('assets/js/jquery.validate.min.js') }}"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="{{ asset('assets/js/floating-wpp.min.js') }}"></script>
    <!--Theme Routes Script-->
    @include('front_end.jQueryRoute')
   
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="{{ asset('public/assets/js/plugins/notifier.js') }}"></script>
    <script src="{{ asset('js/custom.js') }}" defer="defer"></script>
    <script src="{{ asset('public/assets/js/plugins/bootstrap.min.js') }}"></script>
    <script src="{{ asset('public/assets/js/plugins/feather.min.js') }}"></script>
<!-- 
    

    <script src="{{ asset('assets/js/floating-wpp.min.js') }}"></script> -->

    <!--scripts end here-->
   @if ($message = Session::get('success'))
        <script>
            notifier.show('Success', '{!! $message !!}', 'success', site_url +
                '/public/assets/images/notification/ok-48.png', 4000);
        </script>
    @endif

    @if ($message = Session::get('error'))
        <script>
            notifier.show('Error', '{!! $message !!}', 'danger', site_url +
                '/public/assets/images/notification/high_priority-48.png', 4000);
        </script>
    @endif
    @stack('page-script')
</body>
</html>






































