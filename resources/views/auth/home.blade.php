@extends('layouts.auth')

@section('style')
<style>
    .authincation-background {
        background-image: url("../ui/images/background_home.png") !important;
        background-size: cover;
    }
    
    .page-r-logo .light-logo {
        width: 80% !important;
    }
    .page-r-logo .light-logo-title {
        width: 50% !important;
    }

    @media (min-width: 992px) {
        .page-r-logo img {
            width: 300px;
        }
    }

</style>
@endsection

@section('content')
    <div class="row justify-content-center min-vh-100 align-items-center" style="background: #2E2F34;">
        <div class="col-md-6">
            <div class="authincation-content pb-5 mb-5">
                <div class="row no-gutters">
                    <div class="col-xl-12">
                        <div class="page-r-logo">
                            <div class="text-center">
                                <img src="{{asset('ui/images/logo_home.png')}}" class="logo m-auto" alt="logo">
                            </div>

                            <div class="row d-flex pt-5 mt-5 justify-content-center">
                                <div class="col-6 gap-2">
                                    <a href="{{url('register')}}" class="btn btn-primary btn-block btn-rounded btn-gradient btn-lg">新規登録</a>
                                </div>
                                <div class="col-6 gap-2">
                                    <a href="{{url('login')}}" class="btn btn-primary btn-block btn-rounded btn-gradient btn-lg">ログイン</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection