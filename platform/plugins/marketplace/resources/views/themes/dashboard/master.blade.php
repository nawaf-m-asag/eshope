<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    @if (theme_option('favicon'))
        <link rel="shortcut icon" href="{{ RvMedia::getImageUrl(theme_option('favicon')) }}">
    @endif

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ page_title()->getTitle(false) }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet">

    {!! Assets::renderHeader(['core']) !!}

    <link rel="stylesheet" href="{{ asset('vendor/core/plugins/marketplace/fonts/Linearicons/Font/demo-files/demo.css') }}">

    {!! Html::style('/vendor/core/core/base/css/themes/default.css') !!}

    <link rel="stylesheet" href="{{ asset('vendor/core/plugins/marketplace/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/core/plugins/marketplace/css/marketplace.css') }}">

    <!-- Put translation key to translate in VueJS -->
    <script type="text/javascript">
        window.trans = JSON.parse('{!! addslashes(json_encode(trans('plugins/member::dashboard'))) !!}');
        var BotbleVariables = BotbleVariables || {};
        BotbleVariables.languages = {
            tables: {!! json_encode(trans('core/base::tables'), JSON_HEX_APOS) !!},
            notices_msg: {!! json_encode(trans('core/base::notices'), JSON_HEX_APOS) !!},
            pagination: {!! json_encode(trans('pagination'), JSON_HEX_APOS) !!},
            system: {
                'character_remain': '{{ trans('core/base::forms.character_remain') }}'
            }
        };
    </script>
</head>

<body>
@include('core/base::layouts.partials.svg-icon')
<header class="header--mobile">
    <div class="header__left">
        <button class="ps-drawer-toggle"><i class="icon icon-menu"></i></button>
    </div>
    <div class="header__center">
        <a class="ps-logo" href="/">
            @if (theme_option('logo'))
                <img src="{{ RvMedia::getImageUrl(theme_option('logo')) }}" alt="{{ theme_option('site_title') }}">
            @endif
        </a>
    </div>
    <div class="header__right"><a class="header__site-link" href="#"><i class="icon-exit-right"></i></a></div>
</header>
<aside class="ps-drawer--mobile">
    <div class="ps-drawer__header">
        <h4> Menu</h4>
        <button class="ps-drawer__close"><i class="icon icon-cross"></i></button>
    </div>
    <div class="ps-drawer__content">
        <ul class="menu">
            <li><a @if (Route::currentRouteName() == 'marketplace.vendor.dashboard') class="active" @endif href="{{ route('marketplace.vendor.dashboard') }}"><i class="icon-home"></i>{{ __('Dashboard') }}</a></li>
            <li><a @if (Route::currentRouteName() == 'marketplace.vendor.products.index') class="active" @endif href="{{ route('marketplace.vendor.products.index') }}"><i class="icon-database"></i>{{ __('Products') }}</a></li>
            <li><a @if (Route::currentRouteName() == 'marketplace.vendor.orders.index') class="active" @endif href="{{ route('marketplace.vendor.orders.index') }}"><i class="icon-bag2"></i>{{ __('Orders') }}</a></li>
            <li><a @if (Route::currentRouteName() == 'marketplace.vendor.settings') class="active" @endif href="{{ route('marketplace.vendor.settings') }}"><i class="icon-cog"></i>{{ __('Settings') }}</a></li>
            <li><a href="{{ route('customer.overview') }}"><i class="icon-user"></i>{{ __('Customer dashboard') }}</a></li>
        </ul>
    </div>
</aside>
<div class="ps-site-overlay"></div>
<main class="ps-main">
    <div class="ps-main__sidebar">
        <div class="ps-sidebar">
            <div class="ps-sidebar__top">
                <div class="ps-block--user-wellcome">
                    <div class="ps-block__left"><img src="{{ auth('customer')->user()->avatar_url }}" alt="{{ auth('customer')->user()->name }}" width="80" /></div>
                    <div class="ps-block__right">
                        <p>{{ __('Hello') }}, {{ auth('customer')->user()->name }}</p>
                        <small>{{ __('Joined on :date', ['date' => auth('customer')->user()->created_at->format('M d, Y')]) }}</small>
                    </div>
                    <div class="ps-block__action"><a href="{{ route('customer.logout') }}"><i class="icon-exit"></i></a></div>
                </div>
                <div class="ps-block--earning-count"><small>{{ __('Earning') }}</small>
                    <h3>{{ format_price(0) }}</h3>
                </div>
            </div>
            <div class="ps-sidebar__content">
                <div class="ps-sidebar__center">
                    <ul class="menu">
                        <li><a @if (Route::currentRouteName() == 'marketplace.vendor.dashboard') class="active" @endif href="{{ route('marketplace.vendor.dashboard') }}"><i class="icon-home"></i>{{ __('Dashboard') }}</a></li>
                        <li><a @if (Route::currentRouteName() == 'marketplace.vendor.products.index') class="active" @endif href="{{ route('marketplace.vendor.products.index') }}"><i class="icon-database"></i>{{ __('Products') }}</a></li>
                        <li><a @if (Route::currentRouteName() == 'marketplace.vendor.orders.index') class="active" @endif href="{{ route('marketplace.vendor.orders.index') }}"><i class="icon-bag2"></i>{{ __('Orders') }}</a></li>
                        <li><a @if (Route::currentRouteName() == 'marketplace.vendor.settings') class="active" @endif href="{{ route('marketplace.vendor.settings') }}"><i class="icon-cog"></i>{{ __('Settings') }}</a></li>
                    </ul>
                </div>
                <div class="ps-sidebar__footer">
                    <div class="ps-copyright">
                        @if (theme_option('logo'))
                            <img src="{{ RvMedia::getImageUrl(theme_option('logo')) }}" alt="{{ theme_option('site_title') }}">
                        @endif
                        <p>{{ theme_option('copyright') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="ps-main__wrapper">
        <header class="header--dashboard">
            <div class="header__left">
                <h3>{{ page_title()->getTitle(false) }}</h3>
            </div>
            @if (auth('customer')->user()->store && auth('customer')->user()->store->id)
                <div class="header__right"><a class="header__site-link" href="{{ auth('customer')->user()->store->url }}" target="_blank"><span>{{ __('View your store') }}</span><i class="icon-exit-right"></i></a></div>
            @endif
        </header>

        @yield('content')
    </div>
</main>
@if (session()->has('status') || session()->has('success_msg') || session()->has('error_msg') || (isset($errors) && $errors->count() > 0) || isset($error_msg))
    <script type="text/javascript">
        window.noticeMessages = [];
        @if (session()->has('success_msg'))
        noticeMessages.push({'type': 'success', 'message': "{!! addslashes(session('success_msg')) !!}"});
        @endif
        @if (session()->has('status'))
        noticeMessages.push({'type': 'success', 'message': "{!! addslashes(session('status')) !!}"});
        @endif
        @if (session()->has('error_msg'))
        noticeMessages.push({'type': 'error', 'message': "{!! addslashes(session('error_msg')) !!}"});
        @endif
        @if (isset($error_msg))
        noticeMessages.push({'type': 'error', 'message': "{!! addslashes($error_msg) !!}"});
        @endif
        @if (isset($errors))
        @foreach ($errors->all() as $error)
        noticeMessages.push({'type': 'error', 'message': "{!! addslashes($error) !!}"});
        @endforeach
        @endif
    </script>
@endif

<!-- custom code-->
<script src="{{ asset('vendor/core/plugins/marketplace/js/main.js') }}"></script>
<script src="{{ asset('vendor/core/plugins/marketplace/js/marketplace.js') }}"></script>

{!! Assets::renderFooter() !!}
@stack('scripts')
@stack('footer')

@if (session()->has('success_msg') || session()->has('error_msg') || (isset($errors) && $errors->count() > 0) || isset($error_msg))
    <script type="text/javascript">
        $(document).ready(function () {
            @if (session()->has('success_msg'))
            Botble.showSuccess('{{ session('success_msg') }}');
            @endif
            @if (session()->has('error_msg'))
            Botble.showError('{{ session('error_msg') }}');
            @endif
            @if (isset($error_msg))
            Botble.showError('{{ $error_msg }}');
            @endif
            @if (isset($errors))
            @foreach ($errors->all() as $error)
            Botble.showError('{{ $error }}');
            @endforeach
            @endif
        });
    </script>
@endif
</body>

</html>
