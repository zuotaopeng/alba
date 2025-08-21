<!DOCTYPE html>
<html lang="jp" class="h-100">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="author" content="" >
    <meta name="robots" content="" >
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="RAGNAROK、 暗号化資産、仮想通貨、自動取引" />
    <meta property="og:title" content="RAGNAROK、 暗号化資産、仮想通貨、自動取引" />
    <meta property="og:description" content="RAGNAROK、 暗号化資産、仮想通貨、自動取引" />
    <meta name="format-detection" content="telephone=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- PAGE TITLE HERE -->
    <title>{{config('app.name')}}</title>
    <!-- FAVICONS ICON -->
    <link rel="shortcut icon" type="image/png" href="{{asset('ui/images/logo.png')}}" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons" rel="stylesheet">
    <link href="{{asset('ui/css/style.css')}}" rel="stylesheet">
    <link href="{{asset('ui/css/custom_admin.css')}}" rel="stylesheet">

    @yield('style')
</head>

<body class="vh-100">
<div class="authincation h-100">
    <div class="container h-100">
        @yield('content')
    </div>
</div>

<!--**********************************
	Scripts
***********************************-->
<!-- Required vendors -->
<script src="{{asset('ui/vendor/global/global.min.js')}}"></script>
<script src="{{asset('ui/js/dlabnav-init.js')}}"></script>
<script>
    jQuery(document).ready(function(){
        dlabSettingsOptions.version = 'dark';
        dlabSettingsOptions.primary = 'color_13';
        new dlabSettings(dlabSettingsOptions);
    });
</script>
@yield('script')
</body>
</html>