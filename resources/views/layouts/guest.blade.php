@php

    $profile = asset(Storage::url('uploads/logo/'));
    $company_logo = \App\Models\Utility::GetLogo(APP_THEME());

    $favicon = \App\Models\Utility::GetValueByName('favicon', APP_THEME());

    $favicon = get_file($favicon , APP_THEME());

    $cust_darklayout = Cookie::get('cust_darklayout');
    if($cust_darklayout == ''){
        $cust_darklayout = 'off';
    }

    // $cust_theme_bg = \App\Models\Utility::GetValueByName('cust_theme_bg',APP_THEME());
    $cust_theme_bg = Cookie::get('cust_theme_bg');
    if($cust_theme_bg == ''){
        $cust_theme_bg = 'on';
    }
    // $SITE_RTL = \App\Models\Utility::GetValueByName('SITE_RTL',APP_THEME());
    $SITE_RTL = Cookie::get('SITE_RTL');
    if($SITE_RTL == ''){
        $SITE_RTL = 'off';
    }

    $color = Cookie::get('THEME_COLOR');
    if(!empty($color)){
        $color = $color;
    }
    else{
        $color = 'theme-3';
    }

    $lang = Cookie::get('LANGUAGE');
    if (!isset($lang)) {
        $lang = 'en';
    }
    if ($lang == 'ar' || $lang == 'he') {
        $SITE_RTL = 'on';
    }

    $displaylang = App\Models\Utility::languages();

    $theme_id = !empty($theme_id) ? $theme_id : APP_THEME();
    $settings = App\Models\Setting::pluck('value','name')->toArray();

    if(empty($settings['disable_lang'])){
        $settings = App\Models\Utility::Seting();
    }
    $toDisable = explode(',',$settings['disable_lang']);

    foreach($displaylang as $key => $data){
        if (str_contains($settings['disable_lang'], $key)) {
            unset($displaylang[$key]);
        }

    }

    $footer_text = (\App\Models\Utility::GetValueByName('footer_text', APP_THEME()) ? \App\Models\Utility::GetValueByName('footer_text',APP_THEME()) : 'Ecommerce');

@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{isset($SITE_RTL) && $SITE_RTL == 'on'? 'rtl' : '' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Dashboard Template Description" />
    <meta name="keywords" content="Dashboard Template" />
    <meta name="author" content="Rajodiya Infotech" />

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}-@yield('page-title')</title>

    <!-- Favicon icon -->
    <link rel="icon" href="{{(!empty($favicon))? $favicon.'?timestamp=' . time() : $profile.'/logo-sm.svg'.'?timestamp=' . time()}}" type="image/x-icon" />
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/fonts/tabler-icons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/fontawesome.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/fonts/material.css') }}">

    <!-- vendor css -->
    @if ($cust_darklayout == 'on' && $SITE_RTL == 'on')
        <link rel="stylesheet" href="{{ asset('public/assets/css/style-dark.css') }}" id="main-style-link">
        <link rel="stylesheet" href="{{ asset('assets/css/style-rtl.css') }}" id="main-style-link">
    @elseif($cust_darklayout == 'on')
        <link rel="stylesheet" href="{{ asset('public/assets/css/style-dark.css') }}" id="main-style-link">
    @elseif($SITE_RTL == 'on')
        <link rel="stylesheet" href="{{ asset('assets/css/style-rtl.css') }}" id="main-style-link">
    @else
        <link rel="stylesheet" href="{{ asset('public/assets/css/style.css') }}" id="main-style-link">
    @endif
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" id="main-style-link">

    <!-- Scripts -->
    <link rel="stylesheet" href="{{ asset('assets/css/customizer.css') }}">

    <link rel="stylesheet" href="{{ asset('css/custom.css') }}{{ '?v=' . time() }}">
</head>

<body class="theme-3">

    <div class="register-page auth-wrapper auth-v3">
        {{-- <div @if (\Request::route()->getName() == 'register') class="register-page auth-wrapper auth-v3" @else class="auth-wrapper auth-v3" @endif  > --}}
        <div class="login-back-img">
            <img src="{{ asset('assets/images/auth/img-bg-1.svg') }}" alt="" class="img-fluid login-bg-1" />
            <img src="{{ asset('assets/images/auth/img-bg-2.svg') }}" alt="" class="img-fluid login-bg-2" />
            <img src="{{ asset('assets/images/auth/img-bg-3.svg') }}" alt="" class="img-fluid login-bg-3" />
            <img src="{{ asset('assets/images/auth/img-bg-4.svg') }}" alt="" class="img-fluid login-bg-4" />
        </div>
        <div class="bg-auth-side bg-primary login-page"></div>
        <div class="auth-content">
            <nav class="navbar navbar-expand-md navbar-light default">
                <div class="container-fluid pe-2">

                    <a class="navbar-brand" href="#">
                        <img src="{{isset($company_logo) && !empty($company_logo) ? $company_logo.'?timestamp=' . time() : $profile.'/logo-dark.svg'.'?timestamp=' . time()}}" alt="logo" class="brand_icon"/>
                    </a>

                    <div class="d-flex gap-3">
                        <div class="collapse navbar-collapse" id="navbarTogglerDemo01" style="flex-grow: 0;">
                            <ul class="navbar-nav align-items-center ms-auto mb-2 mb-lg-0">
                                   <li class="nav-item">
                                        @include('landingpage::layouts.buttons')
                                    </li>
                            </ul>
                        </div>
                        <div class="dropdown dash-h-item drp-language ecom-lang-drp">
                            <a class="dash-head-link dropdown-toggle arrow-none me-0 bg-primary"
                                data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false"
                                aria-expanded="false">
                                <i class="ti ti-world nocolor"></i>
                                <span class="drp-text">{{ __('EN') }}</span>
                                <i class="ti ti-chevron-down drp-arrow nocolor"></i>
                            </a>

                            <div class="dropdown-menu dash-h-dropdown dropdown-menu-end"
                                onchange="this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value);">

                                @foreach($displaylang as $key => $language)
                                    <a href="{{ route('changelanguage', $key) }}"
                                        class="dropdown-item {{ $lang == $key ? 'text-primary' : '' }}">
                                        <span>{{Str::ucfirst($language)}}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
            <div class="card">
                <div class="row align-items-center justify-content-center text-start">
                    <div class="col-xl-12">
                        <div class="card-body mx-auto my-4 new-login-design">
                            @yield('content')
                        </div>
                    </div>

                </div>
            </div>
            <div class="auth-footer">
                <div class="container-fluid text-center">
                    <div class="row">
                        <div class="col-12">
                            <p class="text-black"> &copy; {{ date('Y') .' '. $footer_text }}
                            </p>
                        </div>
                        <div class="col-6 text-end">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- <script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script> --}}
    <script src="{{ asset('assets/js/vendor-all.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/feather.min.js') }}"></script>


    @php
        $setting =getSuperAdminAllSetting();
    @endphp
    @if (isset($setting['enable_cookie']) && $setting['enable_cookie'] == 'on')
        @include('layouts.cookie_consent')
    @endif
</body>

</html>

