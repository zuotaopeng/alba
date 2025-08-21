<!DOCTYPE html>
<html lang="jp">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="暗号化資産、仮想通貨、自動取引" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <meta name="description" content="RAGNAROK、 暗号化資産、仮想通貨、自動取引" />
    <meta property="og:title" content="RAGNAROK、 暗号化資産、仮想通貨、自動取引" />
    <meta property="og:description" content="RAGNAROK、 暗号化資産、仮想通貨、自動取引" />
    <meta property="og:image" content="{{asset('ui/images/logo.png')}}" />
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
    <link href="{{asset('ui/css/custom.css')}}" rel="stylesheet">
    <style>
        body,
        #preloader {
            background-color: #2d2f34 !important;
        }

        .brand-title-text{
            font-weight: 700;
            color: white !important;
            margin: 0 10px;
            padding-top: 10px;
        }
        .header-right {
            right: 0;
            color: #000;
        }

        .profile-content {

        }
        .profile-content .profile-photo {
            width: 60px;
            height: 60px;
            background: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .profile-content .profile-photo img {
            width: 45%;
        }

        /** ********* 2024/04/12 *********** */
        .pc {
            display: block !important;
        }
        .sp {
            display: none !important;
        }
        @media only screen and (max-width: 576px) {
            .pc {
                display: none !important;
            }
            .sp {
                display: block !important;
            }

            .nav-control.sp {
                display: flex !important;
            }
            .nav-control.sp .hamburger{
                left: -1.4rem;
            }
            [data-sidebar-style="overlay"] .nav-control .hamburger.is-active .line {
                opacity: 1;
            }
        }

        
        .hamburger .line {
            background: linear-gradient(To right, rgb(89, 68, 152), rgb(182, 69, 116) 70%, rgb(232, 63, 77)) !important;
        }
  
    </style>
    @yield('style')
</head>
<body class="app-background">
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
        <a href="{{url('/')}}" class="brand-logo">
            <img src="{{asset('ui/images/logo.png')}}" class="logo-abbr" width="40" alt="logo" style="object-fit:contain;">
        </a>
        <div class="nav-control pc">
            <div class="hamburger">
                <span class="line"></span><span class="line"></span><span class="line"></span>
                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="26" height="26" viewBox="0 0 61.5 51.43">
                    <defs>
                        <style>.cls-1{fill:url(#gradient2) !important;}.cls-2{fill:url(#gradient2-2) !important;}.cls-3{fill:url(#gradient2-3) !important;}</style>
                        <linearGradient id="gradient2" x1="0.13" y1="25.71" x2="61.5" y2="25.71" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#594498"/><stop offset="0.04" stop-color="#5d4598"/><stop offset="0.53" stop-color="#8a4a96"/><stop offset="0.73" stop-color="#b94471"/><stop offset="0.91" stop-color="#db4157"/><stop offset="1" stop-color="#e83f4d"/></linearGradient><linearGradient id="gradient2-2" x1="0" y1="46.21" x2="61.37" y2="46.21" xlink:href="#gradient2"/><linearGradient id="gradient2-3" x1="0" y1="5.21" x2="61.37" y2="5.21" xlink:href="#gradient2"/>
                    </defs>
                    <g id="layer_2" data-name="layer_2">
                        <g id="layer_1-2" data-name="layer_1"><rect class="cls-1" x="0.13" y="20.5" width="61.37" height="10.43" rx="5"/>
                            <rect class="cls-2" y="41" width="61.37" height="10.43" rx="5"/><rect class="cls-3" width="61.37" height="10.43" rx="5"/>
                        </g>
                    </g>
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
                <div class="collapse navbar-collapse justify-content-center">
                    <div class="header-left"></div>

                    {{--  <h2 class="m-0 p-0">@yield('title')</h2>  --}}
                    <img src="{{asset('ui/images/logo_project.png')}}" width="120" alt="logo_project">
                    
                    <div class="header-right d-flex position-absolute">
                        <div class="nav-control sp">
                            <div class="hamburger">
                                <span class="line"></span><span class="line"></span><span class="line"></span>
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="26" height="26" viewBox="0 0 61.5 51.43">
                                    <defs>
                                        <style>.cls-1-sp{fill:url(#gradient2-sp) !important;}.cls-2-sp{fill:url(#gradient2-2-sp) !important;}.cls-3-sp{fill:url(#gradient2-3-sp) !important;}</style>
                                        <linearGradient id="gradient2-sp" x1="0.13" y1="25.71" x2="61.5" y2="25.71" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#594498"/><stop offset="0.04" stop-color="#5d4598"/><stop offset="0.53" stop-color="#8a4a96"/><stop offset="0.73" stop-color="#b94471"/><stop offset="0.91" stop-color="#db4157"/><stop offset="1" stop-color="#e83f4d"/></linearGradient><linearGradient id="gradient2-2-sp" x1="0" y1="46.21" x2="61.37" y2="46.21" xlink:href="#gradient2-sp"/><linearGradient id="gradient2-3-sp" x1="0" y1="5.21" x2="61.37" y2="5.21" xlink:href="#gradient2-sp"/>
                                    </defs>
                                    <g id="layer_2" data-name="layer_2">
                                        <g id="layer_1-2" data-name="layer_1"><rect class="cls-1-sp" x="0.13" y="20.5" width="61.37" height="10.43" rx="5"/>
                                            <rect class="cls-2-sp" y="41" width="61.37" height="10.43" rx="5"/><rect class="cls-3-sp" width="61.37" height="10.43" rx="5"/>
                                        </g>
                                    </g>
                                </svg>
                            </div>
                        </div>
                    </div>
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
            <div class="profile-content m-3 ms-4">
                <div class="profile-photo rounded-circle">
                    <img src="{{asset('ui/images/guide_user.png')}}" alt="">
                </div>
                <h3 class="mt-4 text-white">{{auth()->user()->name}}様</h3>
                <h5 class="text-white">RAGNAROK</h5>
            </div>
            <ul class="metismenu" id="menu" style="padding-top: 0;">
                <li>
                    <a class="" href="{{url('/')}}">
                        <img src="{{asset('ui/images/menu-2.png')}}"/>
                        <span class="ms-2 nav-text">ダッシュボード</span>
                    </a>
                </li>
                <li>
                    <a class="" href="{{url('/rate')}}">
                        <img src="{{asset('ui/images/menu-1.png')}}" />
                        <span class="ms-2 nav-text">リアルタイムレート</span>
                    </a>
                </li>
                <li>
                    <a href="{{url('/setting')}}">
                        <img src="{{asset('ui/images/menu-7.png')}}" />
                        <span class="ms-2 nav-text">設定</span>
                    </a>
                </li>
                <li>
                    <a class="" href="{{url('/risk')}}">
                        <img src="{{asset('ui/images/menu-3.png')}}" />
                        <span class="ms-2 nav-text">リスク管理</span>
                    </a>
                </li>
                <li>
                    <a class="" href="{{url('/orderlist')}}">
                        <img src="{{asset('ui/images/menu-5.png')}}" />
                        <span class="ms-2 nav-text">取引履歴</span>
                    </a>
                </li>
                {{--  <li>
                    <a class="" href="{{url('/diff_money')}}">
                    <img src="{{asset('ui/images/menu-6.png')}}" />
                    <span class="ms-2 nav-text">最大価格差履歴</span>
                    </a>
                </li>  --}}
                <li>
                    <a class="" href="{{url('/password')}}">
                        <img src="{{asset('ui/images/menu-4.png')}}" />
                        <span class="ms-2 nav-text">パスワード変更</span>
                    </a>
                </li>
                <li>
                    <a class="" href="{{asset('manual.pdf')}}" target="_blank">
                        <img src="{{asset('ui/images/menu-8.png')}}" />
                        <span class="ms-2 nav-text">マニュアル</span>
                    </a>
                </li>
                <li>
                    <a class="" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <svg  xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="21px" height="21px">
                            <path fill-rule="evenodd"  fill-opacity="0.502" fill="rgb(255, 255, 255)" d="M10.500,21.000 C4.701,21.000 0.000,16.643 0.000,11.268 C0.000,7.185 2.715,3.695 6.561,2.250 L6.561,4.497 C3.945,5.794 2.165,8.341 2.165,11.274 C2.165,15.519 5.894,18.961 10.493,18.961 C15.093,18.961 18.822,15.519 18.822,11.274 C18.822,8.351 17.054,5.809 14.452,4.509 L14.452,2.254 C18.291,3.702 21.000,7.189 21.000,11.268 C21.000,16.643 16.299,21.000 10.500,21.000 ZM10.726,8.240 L10.287,8.240 C9.735,8.240 9.287,7.793 9.287,7.240 L9.287,1.000 C9.287,0.448 9.735,0.000 10.287,0.000 L10.726,0.000 C11.278,0.000 11.726,0.448 11.726,1.000 L11.726,7.240 C11.726,7.793 11.278,8.240 10.726,8.240 Z"/>
                        </svg>
                        <span class="nav-text">ログアウト</span>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
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
        <div class="container-fluid p-0">
            @yield('content')

            <div class="footer">
                <div class="copyright">
                    <p class="text-black copyright-color fw-bold">©RAGNAROK</p>
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
<script src="{{asset('ui/vendor/datepicker/locales/bootstrap-datepicker.ja.min.js')}}"></script>
<script src="{{asset('ui/vendor/bootstrap-select-country/js/bootstrap-select-country.min.js')}}"></script>

<script src="{{asset('ui/js/dlabnav-init.js')}}"></script>
<script src="{{asset('ui/js/custom.min.js')}}"></script>

<script>
    jQuery(document).ready(function(){
        dlabSettingsOptions.version = 'dark'; // dark, light
        // dlabSettingsOptions.primary = 'color_13'; // color_1 ~ color_13
        new dlabSettings(dlabSettingsOptions);
    });
</script>
@yield('script')
</body>
</html>