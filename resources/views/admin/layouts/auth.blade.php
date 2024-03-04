<!DOCTYPE html>
<html lang="{{ getLangTag(config('app.locale')) }}" dir="ltr">
<head>
    <meta charset="{{ config('larapen.core.charset', 'utf-8') }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    {{-- Tell the browser to be responsive to screen width --}}
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="{{ config('app.name') }}">
    {{-- Favicon icon --}}
    <link rel="icon" type="image/png" sizes="16x16" href="{{ config('settings.app.favicon_url') }}">
    
    <title>{{ isset($title) ? $title.' :: ' . config('app.name') . ' Admin' : config('app.name') . ' Admin' }}</title>
    
    {{-- Encrypted CSRF token for Laravel, in order for Ajax requests to work --}}
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    
    {{-- Specify a default target for all hyperlinks and forms on the page --}}
    <base target="_top"/>
    
    <link rel="canonical" href="{{ url()->current() }}" />
    
    @yield('before_styles')
    
    <link href="{{ url(mix('css/admin.css')) }}" rel="stylesheet">

    @yield('after_styles')
    
    {{-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries --}}
    {{-- WARNING: Respond.js doesn't work if you view the page via file:// --}}
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    
    @yield('captcha_head')
    @yield('recaptcha_head')
</head>

<body>
<div class="main-wrapper">
    
    <?php
    $wrapperStyle = '';
    $logoUrl = '';
    try {
        if (is_link(public_path('storage'))) {
            $bgImgUrl = config('settings.style.login_bg_image_url');
            $wrapperStyle = 'background:url(' . $bgImgUrl . ') no-repeat center center; background-size: cover;';
            $logoUrl = config('settings.app.logo_dark_url');
        }
    } catch (\Throwable $e) {}
    ?>
    
    {{-- Login box.scss --}}
    <div class="auth-wrapper d-flex no-block justify-content-center align-items-center" style="{!! $wrapperStyle !!}">
        <div class="auth-box p-4 bg-white rounded">
    
            <div class="logo text-center mb-5">
                <a href="{{ url('/') }}">
                    <img src="{{ $logoUrl }}" alt="logo" class="img-fluid" style="width:250px; height:auto;">
                </a>
                <hr class="border-0 bg-secondary">
            </div>
            
            @yield('content')
            
        </div>
    </div>
    
</div>

@include('common.js.init')

@yield('before_scripts')

<script>
    {{-- The app's default auth field --}}
    var defaultAuthField = '{{ old('auth_field', getAuthField()) }}';
    var phoneCountry = '';
</script>

<script src="{{ admin_url('common/js/intl-tel-input/countries.js') . getPictureVersion() }}"></script>
<script src="{{ url(mix('js/admin.js')) }}"></script>

{{-- This page plugin js --}}
<script>
    preventPageLoadingInIframe();
    
    $(document).ready(function()
    {
        $('[data-bs-toggle="tooltip"]').tooltip();
        $('.preloader').fadeOut();
        
        {{-- Login and Recover Password --}}
        $('#to-recover').on('click', function() {
            $('#loginform').slideUp();
            $('#recoverform').fadeIn();
        });
        $('#to-login').on('click', function() {
            $('#recoverform').slideUp();
            $('#loginform').fadeIn();
        });
    });
    
    /**
     * Prevent the page to load in IFRAME by redirecting it to the top-level window
     */
    function preventPageLoadingInIframe() {
        try {
            if (window.top.location !== window.location) {
                window.top.location.replace(siteUrl);
            }
        } catch (e) {
            console.error(e);
        }
    }
</script>

@include('admin.layouts.inc.alerts')

@yield('after_scripts')
@yield('captcha_footer')

</body>
</html>
