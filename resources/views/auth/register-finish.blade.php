@extends('layouts.auth')

@section('style')
<style>
    .authincation-background {
        background-image: url("../ui/images/background_register.png") !important;
        background-size: cover;
    }
    
    .page-r-logo .light-logo-title {
        width: 76% !important;
    }

    .footer {
        height: 5%;
        background-color: rgba(0,0,0, 0.45) !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    @media (min-width: 992px) {
        .page-r-logo img {
            width: 300px;
        }
    }
</style>
@endsection

@section('content')
    <div class="row justify-content-center h-45" style="background: #2E2F34;">
        <div class="col-md-6">
            <div class="authincation-content">
                <div class="row no-gutters">
                    <div class="col-xl-12">
                        <div class="auth-form page-r-logo">
                            <div>
                                <div class="text-center">
                                    <img src="{{asset('ui/images/logo_home.png')}}" class="logo m-auto" alt="logo">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row justify-content-center h-50 background-modal">
        <div class="col-md-6 px-5">
            <div class="text-center mt-5 py-5">
                <h5 class="mb-4 fs-32 text-white ">登録が完了しました</h5>
                <h5 class="fs-32 text-white ">RAGNAROKへようこそ</h5>
            </div>

            <div class="text-center pt-4 mt-5">
                <a href="{{url('guide')}}" class="btn btn-primary btn-block btn-rounded btn-gradient btn-lg">始める</a>
            </div>
        </div>
    </div>
@endsection