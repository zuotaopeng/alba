<!DOCTYPE html>
<html lang="jp">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="暗号化資産、仮想通貨、自動取引" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="RAGNAROK、 暗号化資産、仮想通貨、自動取引" />
    <meta property="og:title" content="RAGNAROK、 暗号化資産、仮想通貨、自動取引" />
    <meta property="og:description" content="RAGNAROK、 暗号化資産、仮想通貨、自動取引" />
    <meta property="og:image" content="{{asset('ui/images/favicon.png')}}" />
    <meta name="format-detection" content="telephone=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{config('app.name')}}</title>
    <!-- FAVICONS ICON -->
    <link rel="shortcut icon" type="image/png" href="{{asset('ui/images/logo.png')}}" />
    <link href="{{asset('ui/vendor/wow-master/css/libs/animate.css')}}" rel="stylesheet">
    <link href="{{asset('ui/vendor/bootstrap-select/dist/css/bootstrap-select.min.css')}}" rel="stylesheet">
    <link rel="stylesheet" href="{{asset('ui/vendor/bootstrap-select-country/css/bootstrap-select-country.min.css')}}">
    <link rel="stylesheet" href="{{asset('ui/vendor/jquery-nice-select/css/nice-select.css')}}">
    <link href="{{asset('ui/vendor/datepicker/css/bootstrap-datepicker.min.css')}}" rel="stylesheet">
    <link href="{{asset('ui/vendor/bootstrap-toggle/css/bootstrap4-toggle.min.css')}}" rel="stylesheet">
    <!-- ----swiper-slider---- -->
    <link rel="stylesheet" href="{{asset('ui/vendor/swiper/css/swiper-bundle.min.css')}}">
    <!-- Style css -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons"rel="stylesheet">
    <link href="{{asset('ui/css/style.css')}}" rel="stylesheet">
    <link href="{{asset('ui/css/custom_admin.css')}}" rel="stylesheet">
    <style>
        .brand-title-text{
            font-weight: 700;
            color: white !important;
            margin: 0 10px;
            padding-top: 10px;
        }
    </style>
    @yield('style')
</head>
<body>

<!--*******************
    Preloader start
********************-->
<div id="preloader">
    <div class="loader"></div>
</div>
<!--*******************
    Preloader end
********************-->

<!--**********************************
    Main wrapper start
***********************************-->
<div id="main-wrapper" class="">

    <!--**********************************
        Nav header start
    ***********************************-->
    <div class="nav-header">
        <a href="{{url('/admin')}}" class="brand-logo">
            <img src="{{asset('ui/images/logo.png')}}" class="logo-abbr" width="40" alt="logo">
            <div class="brand-title">
                <span class="brand-title-text">{{config('app.name')}}</span>
            </div>
        </a>
        <div class="nav-control">
            <div class="hamburger">
                <span class="line"></span><span class="line"></span><span class="line"></span>
                <svg width="26" height="26" viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="22" y="11" width="4" height="4" rx="2" fill="#2A353A"/>
                    <rect x="11" width="4" height="4" rx="2" fill="#2A353A"/>
                    <rect x="22" width="4" height="4" rx="2" fill="#2A353A"/>
                    <rect x="11" y="11" width="4" height="4" rx="2" fill="#2A353A"/>
                    <rect x="11" y="22" width="4" height="4" rx="2" fill="#2A353A"/>
                    <rect width="4" height="4" rx="2" fill="#2A353A"/>
                    <rect y="11" width="4" height="4" rx="2" fill="#2A353A"/>
                    <rect x="22" y="22" width="4" height="4" rx="2" fill="#2A353A"/>
                    <rect y="22" width="4" height="4" rx="2" fill="#2A353A"/>
                </svg>
            </div>
        </div>
    </div>
    <!--**********************************
        Nav header end
    ***********************************-->


    <!--**********************************
        Header start
    ***********************************-->
    <div class="header">
        <div class="header-content">
            <nav class="navbar navbar-expand">
                <div class="collapse navbar-collapse justify-content-between">
                    <div class="header-left"></div>
                    <ul class="navbar-nav header-right">
                        <li class="nav-item">
                            <div class="dropdown header-profile2">
                                <div class="header-info2 d-flex align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <h5 class="mb-0">{{auth()->user()->name}}<span class="text-muted" style="margin-left: 6px;">さん</span></h5>
                                        </div>
                                        <a href="" onclick="event.preventDefault();
                                            document.getElementById('logout-form').submit();" class="nav-link" style="font-size: 16px !important;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                                <polyline points="16 17 21 12 16 7"></polyline>
                                                <line x1="21" y1="12" x2="9" y2="12"></line>
                                            </svg>
                                        </a>
                                        <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
                                            @csrf
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </nav>
        </div>

    </div>
    <!--**********************************
    Header end ti-comment-alt
***********************************-->

    <!--**********************************
        Sidebar start
    ***********************************-->
    <div class="dlabnav">
        <div class="dlabnav-scroll">
            <ul class="metismenu" id="menu">
                <li @if(request()->is('admin/register') || request()->is('*users*')) class="mm-active" @endif>
                    <a @if(request()->is('admin/register') || request()->is('*users*')) class="mm-active" @endif href="{{url('/admin')}}">
                        <i class="material-icons-outlined">people_alt</i>
                        <span class="nav-text">ユーザ一管理</span>
                    </a>
                </li>
                <li>
                    <a class="" href="{{url('/admin/password')}}">
                        <i class="material-icons-outlined">lock_outline</i>
                        <span class="nav-text">パスワード変更</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <!--**********************************
        Sidebar end
    ***********************************-->


    <!--**********************************
        Content body start
    ***********************************-->
    <div class="content-body">
        <!-- row -->
        <div class="container-fluid">
            @yield('content')
            <div class="footer">
                <div class="copyright">
                    <p>Copyright © <a href="" target="_blank">BLUE</a> 2023</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!--**********************************
    Scripts
***********************************-->
<!-- Required vendors -->
<script src="{{asset('ui/vendor/global/global.min.js')}}"></script>
<script src="{{asset('ui/vendor/chart.js/Chart.bundle.min.js')}}"></script>
<script src="{{asset('ui/vendor/bootstrap-select/dist/js/bootstrap-select.min.js')}}"></script>
<!-- Apex Chart -->
<script src="{{asset('ui/vendor/apexchart/apexchart.js')}}"></script>
<!-- Chart piety plugin files -->
<script src="{{asset('ui/vendor/peity/jquery.peity.min.js')}}"></script>
<script src="{{asset('ui/vendor/jquery-nice-select/js/jquery.nice-select.min.js')}}"></script>
<script src="{{asset('ui/vendor/bootstrap-toggle/js/bootstrap4-toggle.min.js')}}"></script>
<!-- ----swiper-slider---- -->
<script src="{{asset('ui/vendor/swiper/js/swiper-bundle.min.js')}}"></script>

<!-- Dashboard 1 -->
<script src="{{asset('ui/js/dashboard/dashboard-1.js')}}"></script>
<script src="{{asset('ui/vendor/wow-master/dist/wow.min.js')}}"></script>
<script src="{{asset('ui/vendor/bootstrap-datetimepicker/js/moment.js')}}"></script>
<script src="{{asset('ui/vendor/datepicker/js/bootstrap-datepicker.min.js')}}"></script>
<script src="{{asset('ui/vendor/bootstrap-select-country/js/bootstrap-select-country.min.js')}}"></script>

<script src="{{asset('ui/js/dlabnav-init.js')}}"></script>
<script src="{{asset('ui/js/custom_admin.min.js')}}"></script>

<script>
    jQuery(document).ready(function(){
        dlabSettingsOptions.version = 'dark';
        new dlabSettings(dlabSettingsOptions);
    });
</script>
@yield('script')
</body>
</html>