@extends('layouts.auth')

@section('style')
<style>
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
    <div class="row justify-content-center align-items-center h-45" style="background: #2E2F34;">
        <div class="col-md-6">
            <div class="authincation-content">
                <div class="row no-gutters">
                    <div class="col-xl-12">
                        <div class="auth-form page-r-logo">
                            <div class="nav-header mt-4">
                                <a href="{{url('login')}}" class="fs-32 text-white"><i class="la la-angle-left"></i></a>
                            </div>
                            <div class="h-50">
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
            <h4 class="text-center text-white mt-5 mb-5 pb-3 fs-36">パスワード再設定</h4>
            @include('include.errors')
            @include('include.status')
            <form action="{{ route('password.email') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <input type="email" name="email" id="email" style="padding-left: 5px;" class="form-control form-control-custom" placeholder="メールアドレス">
                    @error('email')
                        <span class="text-danger" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary btn-block btn-rounded btn-gradient btn-lg">パスワード再設リンクを送信</button>
                </div>
            </form>
        </div>
    </div>
@endsection